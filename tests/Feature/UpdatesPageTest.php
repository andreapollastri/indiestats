<?php

namespace Tests\Feature;

use App\Models\User;
use App\Updates\Updater;
use App\Updates\UpdateState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class UpdatesPageTest extends TestCase
{
    use RefreshDatabase;

    private string $tmp;

    protected function setUp(): void
    {
        parent::setUp();

        // a git checkout of its own, so the page sees an installation it can update
        $this->tmp = sys_get_temp_dir().'/updates-page-'.bin2hex(random_bytes(4));
        File::ensureDirectoryExists($this->tmp.'/app/.git');
        file_put_contents($this->tmp.'/app/VERSION', "1.0.0\n");
        config(['updates.enabled' => true, 'updates.repository' => $this->tmp.'/missing']);
        $this->app->instance(Updater::class, new Updater(new UpdateState($this->tmp.'/state.json'), $this->tmp.'/app'));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tmp);

        parent::tearDown();
    }

    public function test_base_user_cannot_access_the_updates_page(): void
    {
        $user = User::factory()->base()->create(['locale' => 'en']);

        $this->actingAs($user)->get(route('updates.index'))->assertForbidden();
        $this->actingAs($user)->getJson(route('updates.status'))->assertForbidden();
        $this->actingAs($user)->post(route('updates.update'))->assertForbidden();
        $this->actingAs($user)->put(route('updates.channel'), ['channel' => 'beta'])->assertForbidden();

        $this->assertSame([], app(Updater::class)->state());
    }

    public function test_admin_sees_the_installed_release_and_the_scheduler_warning(): void
    {
        $this->actingAs($this->admin())
            ->get(route('updates.index'))
            ->assertOk()
            ->assertSee('1.0.0')
            ->assertSee(__('Stable releases'))
            ->assertSee(__('Not checked yet'))
            ->assertSee('data-test="updates-scheduler-missing"', false);
    }

    public function test_the_page_follows_the_admin_language(): void
    {
        $admin = User::factory()->admin()->create(['locale' => 'it']);

        $this->actingAs($admin)->get(route('updates.index'))
            ->assertOk()
            ->assertSee('Aggiornamenti')
            ->assertSee('Release stabili')
            ->assertSee('Forza aggiornamento');
    }

    public function test_an_admin_asks_for_an_update_the_scheduler_starts(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('updates.update'), ['force' => 1])
            ->assertSessionHas('success', __('The update starts within a minute.'));

        $state = app(Updater::class)->state();
        $this->assertTrue($state['requested']['force']);
        $this->assertSame($admin->email, $state['requested']['by']);

        $this->actingAs($admin)->post(route('updates.update'))
            ->assertSessionHas('error', __('An update is already under way.'));
        $this->actingAs($admin)->getJson(route('updates.status'))->assertOk()->assertJson(['busy' => true]);
        $this->actingAs($admin)->get(route('updates.index'))->assertSee('id="pa-update-progress"', false);
    }

    public function test_the_status_endpoint_says_when_nothing_is_under_way(): void
    {
        $this->actingAs($this->admin())
            ->getJson(route('updates.status'))
            ->assertOk()
            ->assertExactJson(['busy' => false, 'status' => null, 'message' => null]);
    }

    public function test_an_admin_chooses_the_channel_and_the_nightly_update(): void
    {
        $this->actingAs($this->admin());

        $this->put(route('updates.channel'), ['channel' => 'beta'])
            ->assertSessionHas('success', __(':app now follows the main branch.', ['app' => config('app.name')]));
        $this->put(route('updates.automatic'), ['auto' => 0])
            ->assertSessionHas('success', __('Automatic updates are off.'));

        $updater = app(Updater::class);
        $this->assertSame('beta', $updater->channel());
        $this->assertFalse($updater->automatic());
        $this->assertSame('unreachable', $updater->state()['waiting_reason']);

        $this->get(route('updates.index'))
            ->assertSee(__('Newest on main'))
            ->assertSee(__('The repository is not reachable.'));

        $this->put(route('updates.channel'), ['channel' => 'nightly'])->assertSessionHasErrors('channel');
        $this->put(route('updates.automatic'), ['auto' => 1])
            ->assertSessionHas('success', __('Automatic updates are on.'));
        $this->assertTrue($updater->automatic());
    }

    public function test_check_now_reports_an_unreachable_repository(): void
    {
        $this->actingAs($this->admin())
            ->from(route('updates.index'))
            ->post(route('updates.check'))
            ->assertRedirect(route('updates.index'))
            ->assertSessionHas('error', __('The repository is not reachable.'));
    }

    public function test_an_installation_the_updater_cannot_update_says_so(): void
    {
        config(['updates.enabled' => false]);
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('updates.index'))
            ->assertOk()
            ->assertSee('data-test="updates-not-managed"', false)
            ->assertSee(__('Updates are off on this installation (a local environment, or APP_UPDATES=false): update it with git.'));
        $this->actingAs($admin)->post(route('updates.update'))->assertNotFound();
    }

    public function test_the_updates_link_is_only_in_the_admin_navigation(): void
    {
        $this->actingAs($this->admin())
            ->get(route('dashboard'))
            ->assertSee(route('updates.index'), false);

        $this->actingAs(User::factory()->base()->create(['locale' => 'en']))
            ->get(route('dashboard'))
            ->assertDontSee(route('updates.index'), false);
    }

    private function admin(): User
    {
        return User::factory()->admin()->create(['locale' => 'en']);
    }
}
