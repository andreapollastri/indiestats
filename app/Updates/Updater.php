<?php

namespace App\Updates;

use Dotenv\Dotenv;
use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\PhpExecutableFinder;
use Throwable;

/**
 * Keeps this installation on the newest build of its channel.
 *
 * Channels: stable, the newest release tag (1.2.3, v1.2.3); beta, the head of main.
 * An update never goes backwards: a build is installed only when it contains the
 * installed commit. Back on stable after beta, the application keeps its main
 * build until a release contains it.
 *
 * Two kinds of installation:
 *   git       the application is a git checkout: the build is checked out in place
 *             (maintenance mode while it is built and migrated)
 *   releases  the application is releases/<x> next to current and shared/ (Cipi,
 *             Deployer): the build is cloned into releases/<build>, shares what the
 *             running release shares, is built and migrated, then current points at it
 * Either way the database is backed up first and put back, with the previous code,
 * when a step fails.
 */
final class Updater
{
    /** A release tag: 1.2.3, v1.2.3 or 1.2. */
    private const TAG = '/^v?\d+\.\d+(\.\d+)?$/';

    /** The name of a release directory the updater made: a tag or main-<sha>, @<time> when installed again. */
    private const BUILD_DIR = '/^(v?\d+\.\d+(\.\d+)?|main-[0-9a-f]{7,40})(@\d{14})?$/';

    private string $base;

    /** @var list<string> */
    private array $log = [];

    /** @var (callable(string): void)|null */
    private $output = null;

    public function __construct(private readonly UpdateState $state, ?string $base = null)
    {
        $base ??= base_path();
        $this->base = rtrim(realpath($base) ?: $base, '/');
    }

    /** Where every line of a run goes too, besides the log (the console of app:update). */
    public function onOutput(callable $output): void
    {
        $this->output = $output;
    }

    // ------------------------------------------------------------- the settings --

    /** @return array<string, mixed> */
    public function state(): array
    {
        return $this->state->all();
    }

    public function version(): string
    {
        return self::versionOf($this->base);
    }

    /** git: a checkout updated in place; releases: one release of a deploy; null: neither, nothing to update. */
    public function layout(): ?string
    {
        if (file_exists($this->base.'/.git')) {
            return 'git';
        }

        $deploy = dirname($this->base, 2);

        if (basename(dirname($this->base)) === 'releases' && is_link($deploy.'/current') && realpath($deploy.'/current') === $this->base) {
            return 'releases';
        }

        return null;
    }

    public function managed(): bool
    {
        return (bool) config('updates.enabled') && $this->layout() !== null;
    }

    /** stable follows the release tags, beta the head of the main branch. */
    public function channel(): string
    {
        return $this->state->get('channel', config('updates.channel')) === 'beta' ? 'beta' : 'stable';
    }

    public function automatic(): bool
    {
        return (bool) $this->state->get('auto', config('updates.auto'));
    }

    public function setChannel(string $channel): void
    {
        $this->state->merge(['channel' => $channel === 'beta' ? 'beta' : 'stable']);

        try {
            $this->check();
        } catch (UpdateFailed) {
            // the waiting reason says the repository is not reachable
        }
    }

    public function setAutomatic(bool $on): void
    {
        $this->state->merge(['auto' => $on]);
    }

    /** An update runs now: it holds the lock while it lives, so a run that died does not count. */
    public function running(): bool
    {
        if ($this->state->get('status') !== 'running') {
            return false;
        }

        $lock = @fopen($this->lockPath(), 'c');

        if ($lock === false) {
            return true;
        }

        $free = flock($lock, LOCK_EX | LOCK_NB);

        if ($free) {
            flock($lock, LOCK_UN);
        }

        fclose($lock);

        return ! $free;
    }

    /** An administrator asked for an update the scheduler has not started yet. */
    public function requested(): bool
    {
        return is_array($this->state->get('requested'));
    }

    /** Ask for an update; the scheduler starts it within a minute (app:update pending). */
    public function request(bool $force = false, ?string $by = null): void
    {
        $this->state->merge(['requested' => ['force' => $force, 'by' => $by, 'at' => now()->toIso8601String()]]);
    }

    /** The scheduler ran in the last few minutes: what an administrator asks for will start. */
    public function schedulerRunning(): bool
    {
        $seen = $this->state->get('scheduler_at');

        return is_string($seen) && strtotime($seen) >= now()->subMinutes(5)->getTimestamp();
    }

    /** The build the channel would install, when the last check found one newer than the installed build. */
    public function available(): ?string
    {
        $state = $this->state->all();
        $target = $state['target'] ?? null;

        return ($state['update_available'] ?? false) === true && is_string($target) && $target !== '' ? $target : null;
    }

    /** Why nothing moves, when the last check said so. */
    public function waiting(): ?string
    {
        $state = $this->state->all();
        $current = (string) ($state['current'] ?? '');
        $target = (string) ($state['target'] ?? '');

        return match ($state['waiting_reason'] ?? null) {
            'no-release' => __('No release is published yet.'),
            'missing' => __('The newest build of this channel is not in the repository.'),
            'behind' => __('This installation runs :current, newer than :target: it stays there until a newer build of this channel contains it.', ['current' => $current, 'target' => $target]),
            'diverged' => __(':target does not contain the installed :current (the history differs): Force update installs it anyway.', ['current' => $current, 'target' => $target]),
            'unreachable' => __('The repository is not reachable.'),
            default => null,
        };
    }

    /** Where a build can be read: the release page of a tag, the commit of a main build. */
    public function releaseUrl(string $build): string
    {
        $repository = preg_replace('/\.git$/', '', rtrim((string) config('updates.repository'), '/'));

        return str_starts_with($build, 'main-')
            ? $repository.'/commit/'.substr($build, 5)
            : $repository.'/releases/tag/'.$build;
    }

    // ------------------------------------------------------------- the history --

    /**
     * What the channel would install, recorded for the Updates page.
     *
     * @return array<string, mixed>
     */
    public function check(): array
    {
        $this->syncMirror();
        $this->recordCheck($this->plan());

        return $this->state->all();
    }

    /** The installed build: a release tag, or main-<sha>. */
    public function installed(): string
    {
        $commit = $this->installedCommit();

        if ($commit === null) {
            return $this->version();
        }

        if ($this->layout() === 'releases') {
            $name = Str::before(basename($this->base), '@');

            if (preg_match(self::BUILD_DIR, $name)) {
                return $name;
            }
        }

        // a main build that became a release goes by the release's name
        if ($tag = $this->tagAt($commit)) {
            return $tag;
        }

        if ($this->state->get('current_commit') === $commit && is_string($this->state->get('current'))) {
            return $this->state->get('current');
        }

        return 'main-'.substr($commit, 0, 7);
    }

    public function installedCommit(): ?string
    {
        if ($this->layout() === 'git') {
            $head = $this->quiet([$this->git(), '-C', $this->base, 'rev-parse', 'HEAD']);

            return $head->successful() ? trim($head->output()) : null;
        }

        // .app-commit: a release this updater made; REVISION: one Deployer made
        foreach (['.app-commit', 'REVISION'] as $file) {
            $commit = trim((string) @file_get_contents($this->base.'/'.$file));

            if (preg_match('/^[0-9a-f]{40}$/', $commit)) {
                return $commit;
            }
        }

        return null;
    }

    /**
     * @return array{ref: ?string, commit: ?string, label: ?string, available: bool, behind: bool, reason: ?string}
     */
    private function plan(): array
    {
        $current = $this->installedCommit();
        $ref = $this->channel() === 'beta' ? 'main' : $this->latestTag();
        $plan = ['ref' => $ref, 'commit' => null, 'label' => null, 'available' => false, 'behind' => false, 'reason' => null];

        if ($ref === null) {
            return ['reason' => 'no-release'] + $plan;
        }

        $commit = $this->commitOf($ref === 'main' ? 'refs/heads/main' : 'refs/tags/'.$ref);

        if ($commit === null) {
            return ['reason' => 'missing'] + $plan;
        }

        $plan['commit'] = $commit;
        $plan['label'] = $ref === 'main' ? 'main-'.substr($commit, 0, 7) : $ref;

        if ($current === null) {
            $plan['available'] = true;
        } elseif ($current === $commit) {
            // the same code; a main build that became a release moves to the release's name
            $plan['available'] = $ref !== 'main' && $this->installed() !== $plan['label'];
        } elseif ($this->contains($commit, $current)) {
            $plan['available'] = true;
        } elseif ($this->contains($current, $commit)) {
            $plan['behind'] = true;
            $plan['reason'] = 'behind';
        } else {
            $plan['reason'] = 'diverged';
        }

        return $plan;
    }

    /** @param  array{label: ?string, available: bool, reason: ?string}  $plan */
    private function recordCheck(array $plan): void
    {
        $main = $this->commitOf('refs/heads/main');

        $this->state->merge([
            'channel' => $this->channel(),
            'version' => $this->version(),
            'current' => $this->installed(),
            'current_commit' => $this->installedCommit(),
            'latest' => $this->latestTag(),
            'main' => $main !== null ? 'main-'.substr($main, 0, 7) : null,
            'target' => $plan['label'],
            'update_available' => $plan['available'],
            'waiting_reason' => $plan['reason'],
            'checked_at' => now()->toIso8601String(),
        ]);
    }

    /** A bare, blob-less mirror of the repository: tags, main, and which commit contains which. */
    private function mirror(): string
    {
        return dirname($this->state->path()).'/repo.git';
    }

    private function syncMirror(): void
    {
        $mirror = $this->mirror();
        $repository = (string) config('updates.repository');

        if (! is_dir($mirror)) {
            File::ensureDirectoryExists(dirname($mirror));

            foreach ([['--filter=blob:none'], []] as $filter) {
                if ($this->quiet([$this->git(), 'clone', '--quiet', '--bare', ...$filter, $repository, $mirror], timeout: 600)->successful()) {
                    break;
                }

                File::deleteDirectory($mirror);
            }
        }

        $fetched = is_dir($mirror)
            && $this->quiet([$this->git(), '--git-dir='.$mirror, 'remote', 'set-url', 'origin', $repository])->successful()
            && $this->quiet([$this->git(), '--git-dir='.$mirror, 'fetch', '--quiet', '--prune', '--prune-tags', '--force', 'origin',
                '+refs/heads/*:refs/heads/*', '+refs/tags/*:refs/tags/*'], timeout: 600)->successful();

        if (! $fetched) {
            $this->state->merge(['checked_at' => now()->toIso8601String(), 'waiting_reason' => 'unreachable']);

            throw new UpdateFailed("The repository is not reachable: {$repository}");
        }
    }

    private function commitOf(string $ref): ?string
    {
        if (! is_dir($this->mirror())) {
            return null;
        }

        $commit = $this->quiet([$this->git(), '--git-dir='.$this->mirror(), 'rev-parse', '-q', '--verify', $ref.'^{commit}']);

        return $commit->successful() ? trim($commit->output()) : null;
    }

    /** @return list<string> the release tags, oldest first: the mirror's, or before the first check the checkout's */
    private function tags(string ...$filter): array
    {
        $repository = match (true) {
            is_dir($this->mirror()) => '--git-dir='.$this->mirror(),
            $this->layout() === 'git' => '--git-dir='.$this->base.'/.git',
            default => null,
        };

        if ($repository === null) {
            return [];
        }

        $tags = array_values(array_filter(
            explode("\n", trim($this->quiet([$this->git(), $repository, 'tag', '-l', ...$filter])->output())),
            fn (string $tag) => (bool) preg_match(self::TAG, $tag),
        ));

        usort($tags, fn (string $a, string $b) => version_compare(self::normalize($a), self::normalize($b)));

        return $tags;
    }

    private function latestTag(): ?string
    {
        $tags = $this->tags();

        return end($tags) ?: null;
    }

    private function tagAt(string $commit): ?string
    {
        $tags = $this->tags('--points-at', $commit);

        return end($tags) ?: null;
    }

    /** The build NEW includes the commit OLD (or is it). */
    private function contains(string $new, string $old): bool
    {
        return $this->quiet([$this->git(), '--git-dir='.$this->mirror(), 'merge-base', '--is-ancestor', $old, $new])->successful();
    }

    /** 1.2 and v1.2.0 are both 1.2.0. */
    public static function normalize(string $version): string
    {
        $version = ltrim(trim($version), 'vV');

        return substr_count($version, '.') === 1 ? $version.'.0' : $version;
    }

    private static function versionOf(string $dir): string
    {
        return trim((string) @file_get_contents($dir.'/VERSION')) ?: 'dev';
    }

    // ------------------------------------------------------------------- doing --

    /**
     * From the scheduler, every minute: note that it runs, and start the update an
     * administrator asked for.
     */
    public function pending(): ?string
    {
        $this->state->merge(['scheduler_at' => now()->toIso8601String()]);
        $requested = $this->state->get('requested');

        if (! is_array($requested) || $this->running() || ! $this->managed()) {
            return null;
        }

        $this->state->merge(['requested' => null]);

        return $this->apply(force: (bool) ($requested['force'] ?? false));
    }

    /**
     * Install the newest build of the channel. Forced, it installs it even when it is
     * the installed one, or when its history differs; never an older one.
     *
     * @return string ok, up-to-date, skipped (automatic updates are off) or failed
     */
    public function apply(bool $auto = false, bool $force = false): string
    {
        if (! $this->managed()) {
            if ($auto) {
                return 'skipped';
            }

            throw new UpdateFailed('This installation is not one the updater can update: neither a git checkout nor a release of a deploy, or APP_UPDATES=false.');
        }

        $lock = $this->lock($auto ? 3600 : 0);

        try {
            return $this->applyLocked($auto, $force);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function applyLocked(bool $auto, bool $force): string
    {
        if ($auto && ! $this->automatic()) {
            return 'skipped';
        }

        $this->log = [];

        try {
            $this->syncMirror();
        } catch (UpdateFailed $e) {
            $this->say($e->getMessage());
            $this->finish('failed', $e->getMessage());

            return 'failed';
        }

        $plan = $this->plan();
        $this->recordCheck($plan);

        if ($plan['commit'] === null || ($plan['behind'] && $force) || (! $force && ! $plan['available'])) {
            $message = match (true) {
                $plan['commit'] === null => (string) $this->waiting(),
                $plan['behind'] => "{$plan['label']} is older than the installed {$this->installed()}: an update never goes back.",
                default => (string) ($this->waiting() ?? "{$this->installed()} is already the newest build of the {$this->channel()} channel."),
            };
            $this->state->merge(['status' => 'up-to-date', 'message' => $message, 'finished_at' => now()->toIso8601String()]);

            return 'up-to-date';
        }

        $from = $this->installed();
        $this->state->merge([
            'status' => 'running',
            'message' => "Updating {$from} → {$plan['label']}",
            'target' => $plan['label'],
            'started_at' => now()->toIso8601String(),
            'finished_at' => null,
            'triggered_automatically' => $auto,
            'forced' => $force,
        ]);
        $this->say("{$from} → {$plan['label']} ({$this->channel()} channel".($force ? ', forced' : '').", {$this->layout()} installation)");

        try {
            $installed = $this->layout() === 'git' ? $this->updateCheckout($plan, $force) : $this->updateRelease($plan);
        } catch (Throwable $e) {
            $message = $e instanceof UpdateFailed ? $e->getMessage() : 'The update stopped: '.$e->getMessage();
            $this->say('FAILED: '.$message);
            $this->finish('failed', $message);

            return 'failed';
        }

        $this->say("{$installed} is live");
        $this->finish('ok', $from === $installed ? "Reinstalled {$installed}." : "Updated {$from} → {$installed}.");

        return 'ok';
    }

    /**
     * A git checkout: the build is checked out in place, in maintenance mode.
     *
     * @param  array{ref: string, commit: string, label: string}  $plan
     */
    private function updateCheckout(array $plan, bool $force): string
    {
        $from = $this->installed();
        $fromCommit = $this->installedCommit();

        $changed = trim($this->quiet([$this->git(), '-C', $this->base, 'status', '--porcelain', '--untracked-files=no'])->output());

        if ($changed !== '') {
            if (! $force) {
                throw new UpdateFailed('Files tracked by git were changed in '.$this->base.': commit or discard them, or use Force update, which discards them.');
            }

            $this->say('discarding the changes to tracked files (forced)');
        }

        // 1. the build
        $this->say("fetching {$plan['ref']}");
        $this->run([$this->git(), '-C', $this->base, 'fetch', '--quiet', '--force', '--no-tags', (string) config('updates.repository'),
            $plan['ref'] === 'main' ? 'refs/heads/main' : 'refs/tags/'.$plan['ref']], timeout: 900);
        $commit = trim($this->quiet([$this->git(), '-C', $this->base, 'rev-parse', 'FETCH_HEAD^{commit}'])->output());
        $label = $plan['ref'] === 'main' ? 'main-'.substr($commit, 0, 7) : $plan['label'];
        $this->checkVersion($plan['ref'], $this->quiet([$this->git(), '-C', $this->base, 'show', $commit.':VERSION'])->output());

        // 2. the database, maintenance
        $backup = $this->backupDatabase($from, $label);
        $cached = is_file($this->base.'/bootstrap/cache/config.php');
        $down = $this->down($this->base);
        $checkedOut = false;
        $migrated = false;

        try {
            // 3. the code, built and migrated
            $this->say("checking out {$label}");
            $checkedOut = true;
            $this->run([$this->git(), '-C', $this->base, 'checkout', '--quiet', '--force', '--detach', $commit]);
            $this->build($this->base, $commit);
            $this->say('migrating');
            $migrated = true;
            $this->artisan($this->base, ['migrate', '--force', '--no-interaction']);

            // 4. caches, workers, maintenance off, and the application boots
            $this->settle($this->base, $cached);
            $this->up($this->base, $down);
            $this->bootCheck($this->base, $label);
        } catch (Throwable $e) {
            $this->say('FAILED: '.$e->getMessage());

            if ($checkedOut && $fromCommit !== null) {
                $this->say("putting {$from} back");
                $this->attempt(fn () => $this->run([$this->git(), '-C', $this->base, 'checkout', '--quiet', '--force', '--detach', $fromCommit]));
                $this->attempt(fn () => $this->build($this->base, $fromCommit));
            }

            if ($migrated && $backup !== null) {
                $this->restoreDatabase($backup, $this->base);
            }

            $this->attempt(fn () => $this->settle($this->base, $cached));
            $this->up($this->base, $down);

            throw $e;
        }

        $this->recordInstalled($label, $commit, $from, $fromCommit, $backup, self::versionOf($this->base));
        $this->pruneBackups();

        return $label;
    }

    /**
     * A release of a deploy: the build goes into releases/<build> next to the running
     * release, shares what it shares, and current points at it once it is migrated.
     *
     * @param  array{ref: string, commit: string, label: string}  $plan
     */
    private function updateRelease(array $plan): string
    {
        $from = $this->installed();
        $fromCommit = $this->installedCommit();
        $releases = dirname($this->base);
        $deploy = dirname($releases);
        $incoming = $releases.'/.update-incoming';

        // 1. the build
        File::deleteDirectory($incoming);
        $this->say("cloning {$plan['ref']}");
        $this->run([$this->git(), 'clone', '--quiet', '-c', 'advice.detachedHead=false', '--depth', '1', '--branch', $plan['ref'],
            (string) config('updates.repository'), $incoming], timeout: 900);
        $commit = trim($this->quiet([$this->git(), '-C', $incoming, 'rev-parse', 'HEAD'])->output());
        $label = $plan['ref'] === 'main' ? 'main-'.substr($commit, 0, 7) : $plan['label'];

        try {
            $this->checkVersion($plan['ref'], (string) @file_get_contents($incoming.'/VERSION'));
        } catch (UpdateFailed $e) {
            File::deleteDirectory($incoming);

            throw $e;
        }

        file_put_contents($incoming.'/.app-commit', $commit."\n");
        file_put_contents($incoming.'/REVISION', $commit."\n");
        File::deleteDirectory($incoming.'/.git');

        $name = $label;

        if (realpath($releases.'/'.$name) === $this->base) {
            $name .= '@'.now()->format('YmdHis');
        }

        File::deleteDirectory($releases.'/'.$name);
        rename($incoming, $releases.'/'.$name);
        $release = $releases.'/'.$name;

        $backup = null;
        $cached = is_file($this->base.'/bootstrap/cache/config.php');
        $down = false;
        $migrated = false;
        $switched = false;

        try {
            // 2. what the running release shares (.env, storage…), then the build
            $this->share($this->base, $release);
            $this->build($release, $commit);

            // 3. the database, maintenance, migrations with the new code
            $backup = $this->backupDatabase($from, $label);
            $down = $this->down($this->base);
            $this->say('migrating');
            $migrated = true;
            $this->artisan($release, ['migrate', '--force', '--no-interaction']);

            // 4. caches of the new release, then current points at it
            if (is_link($this->base.'/public/storage')) {
                $this->artisan($release, ['storage:link', '--force']);
            }

            $this->settle($release, $cached);
            $this->switchTo($deploy, $name);
            $switched = true;
            $this->say("current → releases/{$name}");
            $this->restartWorkers($deploy);
            $this->up($release, $down);
            $this->bootCheck($release, $label);
        } catch (Throwable $e) {
            $this->say('FAILED: '.$e->getMessage());

            if ($switched) {
                $this->say('putting releases/'.basename($this->base).' back');
                $this->switchTo($deploy, basename($this->base));
            }

            if ($migrated && $backup !== null) {
                $this->restoreDatabase($backup, $this->base);
            }

            $this->attempt(fn () => $this->artisan($this->base, ['queue:restart']));
            $this->up($this->base, $down);
            File::deleteDirectory($release);

            throw $e;
        }

        $this->recordInstalled($label, $commit, $from, $fromCommit, $backup, self::versionOf($release), [
            'current_dir' => $name,
            'previous_dir' => basename($this->base),
        ]);
        $this->pruneReleases($releases, $name, basename($this->base));
        $this->pruneBackups();

        return $label;
    }

    /** The VERSION of a release tag says the tag's version. */
    private function checkVersion(string $ref, string $version): void
    {
        if ($ref !== 'main' && self::normalize($version) !== self::normalize($ref)) {
            throw new UpdateFailed("The VERSION file of {$ref} does not say ".self::normalize($ref).'; nothing was changed.');
        }
    }

    /** Composer, then the assets when the build does not carry them. */
    private function build(string $dir, string $commit): void
    {
        $this->say('composer install');
        $this->run([...$this->composer(), 'install', '--no-dev', '--no-interaction', '--prefer-dist', '--no-progress', '--optimize-autoloader'], $dir, 1800);

        if (! $this->needsAssets($dir, $commit)) {
            return;
        }

        $this->say('building the assets');
        $nodeBuild = dirname($dir, 2).'/.deployer/node-build.sh';

        // Cipi's own build script, with the Node it chose for the application
        if ($this->layout() === 'releases' && is_executable($nodeBuild)) {
            $this->run([$nodeBuild, $dir], $dir, 1800);

            return;
        }

        // npm ci needs the lockfile; without one, npm install
        $install = is_file($dir.'/package-lock.json') ? 'ci' : 'install';
        $this->run([$this->npm(), $install, '--no-audit', '--no-fund'], $dir, 1800);
        $this->run([$this->npm(), 'run', 'build'], $dir, 1800);

        if ($this->layout() === 'releases') {
            File::deleteDirectory($dir.'/node_modules');
        }
    }

    /** package.json builds the assets and the commit does not carry them (public/build/manifest.json). */
    private function needsAssets(string $dir, string $commit): bool
    {
        if (! config('updates.assets', true)) {
            return false;
        }

        $package = json_decode((string) @file_get_contents($dir.'/package.json'), true);

        if (! is_array($package) || ! isset($package['scripts']['build'])) {
            return false;
        }

        $tree = $this->quiet([$this->git(), '--git-dir='.$this->mirror(), 'ls-tree', '--name-only', $commit, 'public/build/manifest.json']);

        return ! $tree->successful() || trim($tree->output()) === '';
    }

    /**
     * The new release shares what the running one shares: every symlink at its top
     * (storage and .env on Cipi and Deployer), and its .env when it is a plain file.
     */
    private function share(string $from, string $to): void
    {
        if (! is_link($from.'/storage')) {
            throw new UpdateFailed("storage is not shared between releases ({$from}/storage is not a symlink): an update would lose it.");
        }

        foreach (array_diff(scandir($from) ?: [], ['.', '..']) as $entry) {
            if (! is_link($from.'/'.$entry)) {
                continue;
            }

            if (is_dir($to.'/'.$entry) && ! is_link($to.'/'.$entry)) {
                File::deleteDirectory($to.'/'.$entry);
            } elseif (file_exists($to.'/'.$entry) || is_link($to.'/'.$entry)) {
                unlink($to.'/'.$entry);
            }

            symlink((string) readlink($from.'/'.$entry), $to.'/'.$entry);
        }

        if (is_file($from.'/.env') && ! is_link($from.'/.env')) {
            copy($from.'/.env', $to.'/.env');
        }
    }

    /** Point current at a release directory, atomically, the way Deployer does (relative). */
    private function switchTo(string $deploy, string $name): void
    {
        $next = $deploy.'/current.update';
        @unlink($next);
        symlink('releases/'.$name, $next);
        rename($next, $deploy.'/current');
    }

    /** Cipi keeps the queue workers under its own supervisor: they restart on the new release. */
    private function restartWorkers(string $deploy): void
    {
        if (is_executable('/usr/local/bin/cipi-worker')) {
            $this->attempt(fn () => $this->run(['sudo', '-n', '/usr/local/bin/cipi-worker', 'restart', basename($deploy)]));
        }
    }

    /** Caches as they were (optimize when the configuration was cached), and the workers on the new code. */
    private function settle(string $dir, bool $cached): void
    {
        $this->artisan($dir, $cached ? ['optimize'] : ['view:clear']);
        $this->attempt(fn () => $this->artisan($dir, ['queue:restart']));
    }

    /** Maintenance mode, unless the application already was in it (then it stays). */
    private function down(string $dir): bool
    {
        if (is_file($dir.'/storage/framework/down')) {
            return false;
        }

        return $this->attempt(fn () => $this->artisan($dir, ['down', '--retry=15']));
    }

    private function up(string $dir, bool $down): void
    {
        if ($down) {
            $this->attempt(fn () => $this->artisan($dir, ['up']));
        }
    }

    /** The new code boots: the console kernel, the providers, the configuration. */
    private function bootCheck(string $dir, string $label): void
    {
        try {
            $this->artisan($dir, ['about', '--only=environment']);
        } catch (UpdateFailed) {
            throw new UpdateFailed("{$label} does not boot (php artisan about failed).");
        }
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function recordInstalled(string $label, string $commit, string $from, ?string $fromCommit, ?string $backup, string $version, array $extra = []): void
    {
        $this->state->merge([
            'version' => $version,
            'current' => $label,
            'current_commit' => $commit,
            'previous' => $from,
            'previous_commit' => $fromCommit,
            'backup' => $backup,
            'update_available' => false,
            'waiting_reason' => null,
            ...$extra,
        ]);
    }

    /** Keep the live and the previous release, and the newest others the updater made up to keep_releases. */
    private function pruneReleases(string $releases, string $current, string $previous): void
    {
        $kept = 0;
        $dirs = array_filter(glob($releases.'/*', GLOB_ONLYDIR) ?: [], fn (string $dir) => (bool) preg_match(self::BUILD_DIR, basename($dir)));
        usort($dirs, fn (string $a, string $b) => filemtime($b) <=> filemtime($a));

        foreach ($dirs as $dir) {
            if (in_array(basename($dir), [$current, $previous], true)) {
                continue;
            }

            if (++$kept <= max(0, (int) config('updates.keep_releases') - 2)) {
                continue;
            }

            $this->say('removing the old release '.basename($dir));
            File::deleteDirectory($dir);
        }
    }

    // ------------------------------------------------------------ the database --

    /** @return array<string, mixed> */
    private function connection(): array
    {
        return (array) config('database.connections.'.config('database.default'), []);
    }

    /** A backup of the database before anything changes; null when it is off or there is nothing to back up. */
    private function backupDatabase(string $from, string $to): ?string
    {
        if (! config('updates.backup')) {
            $this->say('database backup: off (APP_UPDATE_BACKUP=false)');

            return null;
        }

        $db = $this->connection();
        $dir = dirname($this->state->path()).'/backups';
        File::ensureDirectoryExists($dir, 0750);
        $file = $dir.'/'.Str::slug($from.'-to-'.$to).'-'.now()->format('YmdHis');

        switch ($db['driver'] ?? null) {
            case 'sqlite':
                $path = (string) ($db['database'] ?? '');

                if ($path === '' || $path === ':memory:' || ! is_file($path)) {
                    return null;
                }

                $file .= '.sqlite';
                $this->say('backing up the database to '.basename($file));
                copy($path, $file) || throw new UpdateFailed('The database backup failed; nothing was changed.');
                break;

            case 'mysql':
            case 'mariadb':
            case 'pgsql':
                $file .= '.sql.gz';
                $this->say('backing up the database to '.basename($file));
                [$command, $env] = $this->dumpCommand($db);
                $dump = Process::timeout(3600)->env($env + ['OUT' => $file])
                    ->run(['bash', '-c', 'set -o pipefail; "$@" | gzip -c > "$OUT"', 'bash', ...$command]);

                if ($dump->failed()) {
                    @unlink($file);

                    throw new UpdateFailed('The database backup failed; nothing was changed: '.trim($dump->errorOutput()));
                }
                break;

            default:
                $this->say('database backup: not available for the '.($db['driver'] ?? 'unknown').' driver');

                return null;
        }

        @chmod($file, 0600);

        return $file;
    }

    /** Put the database back as it was dumped, without the tables a migration added. */
    private function restoreDatabase(string $backup, string $dir): void
    {
        $this->say('restoring the database from '.basename($backup));
        $db = $this->connection();

        try {
            if (($db['driver'] ?? null) === 'sqlite') {
                copy($backup, (string) $db['database']) || throw new UpdateFailed('copy failed');

                return;
            }

            $this->artisan($dir, ['db:wipe', '--force', '--drop-views']);
            [$command, $env] = $this->restoreCommand($db);
            $restore = Process::timeout(3600)->env($env + ['IN' => $backup])
                ->run(['bash', '-c', 'set -o pipefail; gunzip -c "$IN" | "$@"', 'bash', ...$command]);
            $restore->successful() || throw new UpdateFailed(trim($restore->errorOutput()));
        } catch (Throwable $e) {
            $this->say("THE DATABASE COULD NOT BE RESTORED ({$e->getMessage()}): restore {$backup} by hand");
        }
    }

    /**
     * @param  array<string, mixed>  $db
     * @return array{list<string>, array<string, string>}
     */
    private function dumpCommand(array $db): array
    {
        if ($db['driver'] === 'pgsql') {
            return [[$this->tool('pg_dump'), '--no-owner', '--no-acl', ...$this->pgsqlArguments($db)], ['PGPASSWORD' => (string) ($db['password'] ?? '')]];
        }

        $dump = $this->tool('mysqldump', 'mariadb-dump');

        return [[$dump, '--single-transaction', '--quick', '--routines', '--triggers', '--no-tablespaces', ...$this->mysqlArguments($db)],
            ['MYSQL_PWD' => (string) ($db['password'] ?? '')]];
    }

    /**
     * @param  array<string, mixed>  $db
     * @return array{list<string>, array<string, string>}
     */
    private function restoreCommand(array $db): array
    {
        if ($db['driver'] === 'pgsql') {
            return [[$this->tool('psql'), '--quiet', '--set=ON_ERROR_STOP=1', ...$this->pgsqlArguments($db)], ['PGPASSWORD' => (string) ($db['password'] ?? '')]];
        }

        return [[$this->tool('mysql', 'mariadb'), ...$this->mysqlArguments($db)], ['MYSQL_PWD' => (string) ($db['password'] ?? '')]];
    }

    /**
     * @param  array<string, mixed>  $db
     * @return list<string>
     */
    private function mysqlArguments(array $db): array
    {
        $server = ! empty($db['unix_socket'])
            ? ['--socket='.$db['unix_socket']]
            : ['--host='.($db['host'] ?? '127.0.0.1'), '--port='.($db['port'] ?? 3306)];

        return [...$server, '--user='.($db['username'] ?? ''), (string) $db['database']];
    }

    /**
     * @param  array<string, mixed>  $db
     * @return list<string>
     */
    private function pgsqlArguments(array $db): array
    {
        return ['--host='.($db['host'] ?? '127.0.0.1'), '--port='.($db['port'] ?? 5432), '--username='.($db['username'] ?? ''), '--dbname='.$db['database']];
    }

    private function pruneBackups(): void
    {
        $backups = glob(dirname($this->state->path()).'/backups/*') ?: [];
        usort($backups, fn (string $a, string $b) => filemtime($b) <=> filemtime($a));

        foreach (array_slice($backups, (int) config('updates.keep_backups')) as $old) {
            @unlink($old);
        }
    }

    // ----------------------------------------------------------------- helpers --

    private function git(): string
    {
        return (string) config('updates.git', 'git');
    }

    private function php(): string
    {
        return (string) (config('updates.php') ?: (new PhpExecutableFinder)->find(false) ?: 'php');
    }

    /** @return list<string> Composer on the application's PHP, not on the first PHP of the PATH */
    private function composer(): array
    {
        $composer = config('updates.composer') ?: (new ExecutableFinder)->find('composer');

        if (! $composer) {
            throw new UpdateFailed('Composer is not on the PATH: set APP_UPDATE_COMPOSER to its path.');
        }

        $head = (string) @file_get_contents($composer, false, null, 0, 128);

        return str_starts_with($head, '<?php') || str_contains(strtok($head, "\n") ?: '', 'php')
            ? [$this->php(), $composer]
            : [$composer];
    }

    private function npm(): string
    {
        return (string) (config('updates.npm') ?: (new ExecutableFinder)->find('npm')
            ?: throw new UpdateFailed('npm is not on the PATH: set APP_UPDATE_NPM to its path.'));
    }

    private function tool(string ...$names): string
    {
        foreach ($names as $name) {
            if ($path = (new ExecutableFinder)->find($name)) {
                return $path;
            }
        }

        throw new UpdateFailed(implode(' or ', $names).' is not installed; nothing was changed.');
    }

    /** @param  list<string>  $arguments */
    private function artisan(string $dir, array $arguments): void
    {
        $this->run([$this->php(), $dir.'/artisan', ...$arguments], $dir, 1800);
    }

    /**
     * A step of the update; its output goes to the log, a failure stops the update.
     *
     * @param  list<string>  $command
     */
    private function run(array $command, ?string $cwd = null, int $timeout = 600): void
    {
        $result = Process::path($cwd ?? $this->base)->timeout($timeout)->env($this->environment())
            ->run($command, function (string $type, string $output): void {
                foreach (preg_split('/\R/', rtrim($output)) ?: [] as $line) {
                    if (trim($line) !== '') {
                        $this->say('  '.$line, stamp: false);
                    }
                }
            });

        if ($result->failed()) {
            $step = implode(' ', array_map(fn (string $part) => str_starts_with($part, '/') ? basename($part) : $part, array_slice($command, 0, 4)));

            throw new UpdateFailed("{$step} failed (exit {$result->exitCode()}).");
        }
    }

    /**
     * A command whose output only the updater reads.
     *
     * @param  list<string>  $command
     */
    private function quiet(array $command, int $timeout = 120): ProcessResult
    {
        return Process::timeout($timeout)->env($this->environment())->run($command);
    }

    /**
     * The environment of the commands: what this process loaded from .env is not
     * passed on, so each command (the new code above all) reads .env itself.
     *
     * @return array<string, string|false>
     */
    private function environment(): array
    {
        $loaded = array_keys(Dotenv::parse((string) @file_get_contents($this->base.'/.env')));

        return array_fill_keys($loaded, false) + ['COMPOSER_NO_INTERACTION' => '1', 'GIT_TERMINAL_PROMPT' => '0'];
    }

    /** A step whose failure the update survives; whether it worked. */
    private function attempt(callable $step): bool
    {
        try {
            $step();

            return true;
        } catch (Throwable $e) {
            $this->say('  (ignored) '.$e->getMessage(), stamp: false);

            return false;
        }
    }

    private function lockPath(): string
    {
        return dirname($this->state->path()).'/update.lock';
    }

    /** One update at a time; the nightly run waits for one under way. @return resource */
    private function lock(int $wait)
    {
        File::ensureDirectoryExists(dirname($this->lockPath()));
        $lock = fopen($this->lockPath(), 'c');
        $until = time() + $wait;

        while (! flock($lock, LOCK_EX | LOCK_NB)) {
            if (time() >= $until) {
                fclose($lock);

                throw new UpdateFailed('Another update is running.');
            }

            sleep(5);
        }

        return $lock;
    }

    private function say(string $line, bool $stamp = true): void
    {
        $line = $stamp ? '['.now()->format('Y-m-d H:i:s').'] '.$line : $line;
        $this->log[] = $line;
        @file_put_contents(dirname($this->state->path()).'/update.log', $line."\n", FILE_APPEND);

        if ($this->output !== null) {
            ($this->output)($line);
        }
    }

    private function finish(string $status, string $message): void
    {
        $this->state->merge([
            'status' => $status,
            'message' => $message,
            'finished_at' => now()->toIso8601String(),
            'log' => array_slice($this->log, -80),
        ]);
    }
}
