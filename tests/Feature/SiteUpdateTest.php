<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_site_name_and_allowed_domains(): void
    {
        $user = User::factory()->admin()->create();

        $site = $user->ownedSites()->create([
            'name' => 'Blog demo',
            'allowed_domains' => 'example.com',
        ]);

        $response = $this->actingAs($user)->put(route('sites.update', $site), [
            'name' => 'Blog aggiornato',
            'allowed_domains' => 'nuovo.esempio.com, www.nuovo.esempio.com',
        ]);

        $response
            ->assertRedirect(route('sites.index'))
            ->assertSessionHas('success');

        $site->refresh();

        $this->assertSame('Blog aggiornato', $site->name);
        $this->assertSame('nuovo.esempio.com, www.nuovo.esempio.com', $site->allowed_domains);
    }

    public function test_site_update_is_reflected_on_sites_index(): void
    {
        $user = User::factory()->admin()->create();

        $site = $user->ownedSites()->create([
            'name' => 'Prima',
            'allowed_domains' => 'prima.com',
        ]);

        $this->actingAs($user)->put(route('sites.update', $site), [
            'name' => 'Dopo',
            'allowed_domains' => 'dopo.com',
        ])->assertRedirect(route('sites.index'));

        $response = $this->actingAs($user)->get(route('sites.index'));

        $response->assertOk();
        $response->assertSee('Dopo', false);
        $response->assertSee('dopo.com', false);
        $response->assertSee('id="editSiteModal"', false);
    }

    public function test_non_admin_cannot_update_site(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $site = $admin->ownedSites()->create([
            'name' => 'Blog demo',
            'allowed_domains' => 'example.com',
        ]);

        $site->assignedUsers()->syncWithoutDetaching([$user->id]);

        $this->actingAs($user)->put(route('sites.update', $site), [
            'name' => 'Tentativo',
            'allowed_domains' => 'hack.com',
        ])->assertForbidden();

        $site->refresh();

        $this->assertSame('Blog demo', $site->name);
        $this->assertSame('example.com', $site->allowed_domains);
    }

    public function test_site_update_requires_allowed_domains(): void
    {
        $user = User::factory()->admin()->create();

        $site = $user->ownedSites()->create([
            'name' => 'Blog demo',
            'allowed_domains' => 'example.com',
        ]);

        $response = $this->actingAs($user)->from(route('sites.index'))->put(route('sites.update', $site), [
            'name' => 'Blog demo',
            'allowed_domains' => '   ',
        ]);

        $response
            ->assertRedirect(route('sites.index'))
            ->assertSessionHasErrors('allowed_domains')
            ->assertSessionHas('edit_site_public_key', $site->public_key);

        $site->refresh();

        $this->assertSame('example.com', $site->allowed_domains);
    }

    public function test_public_key_is_not_changed_when_updating_site(): void
    {
        $user = User::factory()->admin()->create();

        $site = $user->ownedSites()->create([
            'name' => 'Blog demo',
            'allowed_domains' => 'example.com',
        ]);

        $publicKey = $site->public_key;

        $this->actingAs($user)->put(route('sites.update', $site), [
            'name' => 'Nuovo nome',
            'allowed_domains' => 'nuovo.com',
        ])->assertRedirect(route('sites.index'));

        $this->assertSame($publicKey, $site->fresh()->public_key);
    }
}
