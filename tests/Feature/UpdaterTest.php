<?php

namespace Tests\Feature;

use App\Updates\Updater;
use App\Updates\UpdateState;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * The updater against real git repositories: an origin with tags and main, and an
 * installation that is either a checkout of it or a release of a deploy. Composer
 * and artisan are stand-ins that note what they were asked to do.
 */
class UpdaterTest extends TestCase
{
    private string $tmp;

    private string $origin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmp = sys_get_temp_dir().'/updater-test-'.bin2hex(random_bytes(4));
        $this->origin = $this->tmp.'/origin';
        File::ensureDirectoryExists($this->origin);
        file_put_contents($this->tmp.'/composer', "#!/bin/sh\nexit 0\n");
        chmod($this->tmp.'/composer', 0755);

        $this->git($this->origin, 'init', '-q', '-b', 'main');
        file_put_contents($this->origin.'/artisan', <<<'PHP'
            <?php
            $command = implode(' ', array_slice($argv, 1));
            file_put_contents(__DIR__.'/artisan.log', $command.PHP_EOL, FILE_APPEND);
            if (str_starts_with($command, 'migrate') && is_file(dirname(__DIR__).'/db.sqlite')) {
                file_put_contents(dirname(__DIR__).'/db.sqlite', 'migrated by '.trim(file_get_contents(__DIR__.'/VERSION')));
            }
            $fail = is_file(__DIR__.'/.fail') ? trim(file_get_contents(__DIR__.'/.fail')) : '';
            exit($fail !== '' && str_starts_with($command, $fail) ? 1 : 0);
            PHP);

        config([
            'updates.enabled' => true,
            'updates.repository' => $this->origin,
            'updates.channel' => 'stable',
            'updates.auto' => true,
            'updates.backup' => false,
            'updates.composer' => $this->tmp.'/composer',
            'updates.php' => PHP_BINARY,
            'updates.keep_releases' => 3,
            'updates.keep_backups' => 5,
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tmp);

        parent::tearDown();
    }

    public function test_stable_waits_for_a_first_release(): void
    {
        $this->commit('1.0.0', 'first');
        $updater = $this->checkout();

        $updater->check();

        $this->assertSame('no-release', $updater->state()['waiting_reason']);
        $this->assertNull($updater->available());
    }

    public function test_stable_installs_the_newest_release_tag_in_a_checkout(): void
    {
        $this->commit('1.0.0', 'first');
        $this->tag('1.0.0');
        $updater = $this->checkout();
        $this->commit('1.1.0', 'second');
        $this->tag('1.1.0');
        $this->commit('1.2.0', 'unreleased work on main');

        $updater->check();
        $this->assertSame('1.1.0', $updater->available());
        $this->assertSame('1.0.0', $updater->installed());

        $this->assertSame('ok', $updater->apply());

        $this->assertSame('1.1.0', trim(file_get_contents($this->tmp.'/app/VERSION')));
        $this->assertSame('1.1.0', $updater->installed());
        $this->assertSame('ok', $updater->state()['status']);
        $this->assertSame('Updated 1.0.0 → 1.1.0.', $updater->state()['message']);
        $artisan = file_get_contents($this->tmp.'/app/artisan.log');
        $this->assertStringContainsString('down --retry=15', $artisan);
        $this->assertStringContainsString('migrate --force', $artisan);
        $this->assertStringContainsString('up', $artisan);

        $this->assertSame('up-to-date', $updater->apply());
    }

    public function test_beta_follows_main_and_stable_never_goes_back(): void
    {
        $this->commit('1.0.0', 'first');
        $this->tag('1.0.0');
        $updater = $this->checkout();
        $head = $this->commit('1.1.0', 'on main');

        $updater->setChannel('beta');
        $this->assertSame('main-'.substr($head, 0, 7), $updater->available());
        $this->assertSame('ok', $updater->apply());
        $this->assertSame($head, $updater->installedCommit());

        // back on stable: 1.0.0 is older than the installed main build
        $updater->setChannel('stable');
        $this->assertNull($updater->available());
        $this->assertSame('behind', $updater->state()['waiting_reason']);
        $this->assertSame('up-to-date', $updater->apply(force: true));
        $this->assertSame($head, $updater->installedCommit());

        // a release that contains the main build
        $this->tag('1.1.0');
        $updater->check();
        $this->assertSame('1.1.0', $updater->installed());
        $this->assertNull($updater->available());
    }

    public function test_a_release_whose_history_differs_waits_for_force_update(): void
    {
        $this->commit('1.0.0', 'first');
        $this->tag('1.0.0');
        $updater = $this->checkout();
        $this->git($this->tmp.'/app', '-c', 'user.name=t', '-c', 'user.email=t@t', 'commit', '-q', '--allow-empty', '-m', 'local only');
        $this->commit('1.1.0', 'second');
        $this->tag('1.1.0');

        $updater->check();
        $this->assertSame('diverged', $updater->state()['waiting_reason']);
        $this->assertSame('up-to-date', $updater->apply());

        $this->assertSame('ok', $updater->apply(force: true));
        $this->assertSame('1.1.0', $updater->installed());
    }

    public function test_a_tag_whose_version_file_disagrees_changes_nothing(): void
    {
        $this->commit('1.0.0', 'first');
        $this->tag('1.0.0');
        $updater = $this->checkout();
        $this->commit('1.0.0', 'forgot to bump');
        $this->tag('1.1.0');

        $this->assertSame('failed', $updater->apply());

        $this->assertStringContainsString('does not say 1.1.0', $updater->state()['message']);
        $this->assertSame('1.0.0', $updater->installed());
    }

    public function test_changed_tracked_files_stop_an_update_unless_forced(): void
    {
        $this->commit('1.0.0', 'first');
        $this->tag('1.0.0');
        $updater = $this->checkout();
        $this->commit('1.1.0', 'second');
        $this->tag('1.1.0');
        file_put_contents($this->tmp.'/app/VERSION', "patched\n");

        $this->assertSame('failed', $updater->apply());
        $this->assertStringContainsString('Files tracked by git were changed', $updater->state()['message']);

        $this->assertSame('ok', $updater->apply(force: true));
        $this->assertSame('1.1.0', trim(file_get_contents($this->tmp.'/app/VERSION')));
    }

    public function test_a_failed_migration_puts_the_code_and_the_database_back(): void
    {
        config([
            'updates.backup' => true,
            'database.default' => 'update_test',
            'database.connections.update_test' => ['driver' => 'sqlite', 'database' => $this->tmp.'/db.sqlite'],
        ]);
        file_put_contents($this->tmp.'/db.sqlite', 'data of 1.0.0');
        $this->commit('1.0.0', 'first');
        $this->tag('1.0.0');
        $updater = $this->checkout();
        $before = $updater->installedCommit();
        $this->commit('1.1.0', 'a broken migration', ['.fail' => 'migrate']);
        $this->tag('1.1.0');

        $this->assertSame('failed', $updater->apply());

        $this->assertSame($before, $updater->installedCommit());
        $this->assertSame('data of 1.0.0', file_get_contents($this->tmp.'/db.sqlite'));
        $this->assertStringContainsString('migrate --force', $updater->state()['message']);
        $this->assertNotEmpty(glob($this->tmp.'/state/backups/*.sqlite'));
        $this->assertStringEndsWith("up\n", file_get_contents($this->tmp.'/app/artisan.log'));
    }

    public function test_a_release_of_a_deploy_is_built_next_to_the_running_one(): void
    {
        $this->commit('1.0.0', 'first');
        $this->tag('1.0.0');
        $deploy = $this->tmp.'/deploy';
        File::ensureDirectoryExists($deploy.'/shared/storage/framework');
        file_put_contents($deploy.'/shared/.env', "APP_NAME=Test\n");
        $this->git($this->tmp, 'clone', '-q', '--branch', '1.0.0', $this->origin, $deploy.'/releases/1');
        File::deleteDirectory($deploy.'/releases/1/.git');
        file_put_contents($deploy.'/releases/1/REVISION', $this->revParse('1.0.0'));
        symlink('../../shared/storage', $deploy.'/releases/1/storage');
        symlink('../../shared/.env', $deploy.'/releases/1/.env');
        symlink('releases/1', $deploy.'/current');
        $updater = new Updater(new UpdateState($this->tmp.'/state/state.json'), $deploy.'/releases/1');
        $this->commit('1.1.0', 'second');
        $this->tag('1.1.0');

        $this->assertSame('releases', $updater->layout());
        $updater->check();
        $this->assertSame('1.0.0', $updater->installed());
        $this->assertSame('ok', $updater->apply());

        $this->assertSame(realpath($deploy.'/releases/1.1.0'), realpath($deploy.'/current'));
        $this->assertSame('releases/1.1.0', readlink($deploy.'/current'));
        $this->assertSame('../../shared/storage', readlink($deploy.'/releases/1.1.0/storage'));
        $this->assertSame('../../shared/.env', readlink($deploy.'/releases/1.1.0/.env'));
        $this->assertSame($this->revParse('1.1.0'), trim(file_get_contents($deploy.'/releases/1.1.0/.app-commit')));
        $this->assertDirectoryDoesNotExist($deploy.'/releases/1.1.0/.git');
        $this->assertDirectoryExists($deploy.'/releases/1');
        $this->assertSame('1', $updater->state()['previous_dir']);
        $this->assertStringContainsString('migrate --force', file_get_contents($deploy.'/releases/1.1.0/artisan.log'));
        $this->assertStringContainsString('down --retry=15', file_get_contents($deploy.'/releases/1/artisan.log'));

        // from the new release: nothing newer
        $next = new Updater(new UpdateState($this->tmp.'/state/state.json'), $deploy.'/current');
        $this->assertSame('1.1.0', $next->installed());
        $this->assertSame('up-to-date', $next->apply());
    }

    public function test_a_release_that_fails_leaves_current_where_it_was(): void
    {
        $this->commit('1.0.0', 'first');
        $this->tag('1.0.0');
        $deploy = $this->tmp.'/deploy';
        File::ensureDirectoryExists($deploy.'/shared/storage');
        $this->git($this->tmp, 'clone', '-q', '--branch', '1.0.0', $this->origin, $deploy.'/releases/1');
        File::deleteDirectory($deploy.'/releases/1/.git');
        symlink('../../shared/storage', $deploy.'/releases/1/storage');
        symlink('releases/1', $deploy.'/current');
        $updater = new Updater(new UpdateState($this->tmp.'/state/state.json'), $deploy.'/releases/1');
        $this->commit('1.1.0', 'does not boot', ['.fail' => 'about']);
        $this->tag('1.1.0');

        $this->assertSame('failed', $updater->apply());

        $this->assertSame('releases/1', readlink($deploy.'/current'));
        $this->assertDirectoryDoesNotExist($deploy.'/releases/1.1.0');
        $this->assertStringContainsString('1.1.0 does not boot', $updater->state()['message']);
    }

    public function test_the_scheduler_starts_the_update_an_administrator_asked_for(): void
    {
        $this->commit('1.0.0', 'first');
        $this->tag('1.0.0');
        $updater = $this->checkout();
        $this->commit('1.1.0', 'second');
        $this->tag('1.1.0');

        $this->assertNull($updater->pending());
        $this->assertTrue($updater->schedulerRunning());

        $updater->request(by: 'admin@example.com');
        $this->assertTrue($updater->requested());
        $this->assertSame('ok', $updater->pending());
        $this->assertFalse($updater->requested());
        $this->assertSame('1.1.0', $updater->installed());
    }

    public function test_the_nightly_run_does_nothing_while_automatic_updates_are_off(): void
    {
        $this->commit('1.0.0', 'first');
        $this->tag('1.0.0');
        $updater = $this->checkout();
        $this->commit('1.1.0', 'second');
        $this->tag('1.1.0');

        $updater->setAutomatic(false);

        $this->assertSame('skipped', $updater->apply(auto: true));
        $this->assertSame('1.0.0', $updater->installed());
    }

    public function test_release_tags_are_compared_as_versions(): void
    {
        $this->assertSame('1.2.0', Updater::normalize('v1.2'));
        $this->assertSame('1.10.0', Updater::normalize('1.10.0'));
        $this->assertTrue(version_compare(Updater::normalize('1.10.0'), Updater::normalize('v1.9'), '>'));
    }

    private function checkout(): Updater
    {
        $this->git($this->tmp, 'clone', '-q', $this->origin, $this->tmp.'/app');

        return new Updater(new UpdateState($this->tmp.'/state/state.json'), $this->tmp.'/app');
    }

    /** @param  array<string, string>  $files */
    private function commit(string $version, string $message, array $files = []): string
    {
        file_put_contents($this->origin.'/VERSION', $version."\n");
        @unlink($this->origin.'/.fail');

        foreach ($files as $name => $content) {
            file_put_contents($this->origin.'/'.$name, $content);
        }

        $this->git($this->origin, 'add', '-A');
        $this->git($this->origin, '-c', 'user.name=t', '-c', 'user.email=t@t', 'commit', '-q', '--allow-empty', '-m', $message);

        return $this->revParse('HEAD');
    }

    private function tag(string $tag): void
    {
        $this->git($this->origin, 'tag', $tag);
    }

    private function revParse(string $ref): string
    {
        return trim((string) shell_exec('git -C '.escapeshellarg($this->origin).' rev-parse '.escapeshellarg($ref.'^{commit}')));
    }

    private function git(string $dir, string ...$arguments): void
    {
        exec('git -C '.escapeshellarg($dir).' '.implode(' ', array_map('escapeshellarg', $arguments)).' 2>&1', $output, $code);
        $this->assertSame(0, $code, implode("\n", $output));
    }
}
