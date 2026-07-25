<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_sites_index_renders_datatable_for_admin(): void
    {
        $user = User::factory()->admin()->create();

        $user->ownedSites()->create([
            'name' => 'Blog demo',
            'allowed_domains' => 'example.com',
        ]);

        $response = $this->actingAs($user)->get(route('sites.index'));

        $response->assertOk();
        $response->assertSee('id="pa-sites-index-table"', false);
        $response->assertSee('id="pa-sites-index-config"', false);
        $response->assertSee('id="pa-sites-index-filter"', false);
        $response->assertSee(__('Domini consentiti'), false);
        $response->assertDontSee('<th>'.__('Chiave').'</th>', false);
        $response->assertSee('id="createSiteModal"', false);
        $response->assertSee('data-bs-target="#createSiteModal"', false);
        $response->assertSee('Blog demo', false);
    }

    public function test_sites_index_lists_sites_alphabetically(): void
    {
        $user = User::factory()->admin()->create();

        $user->ownedSites()->create([
            'name' => 'Zebra Analytics',
            'allowed_domains' => 'zebra.test',
        ]);
        $user->ownedSites()->create([
            'name' => 'alpha blog',
            'allowed_domains' => 'alpha.test',
        ]);
        $user->ownedSites()->create([
            'name' => 'Middle Site',
            'allowed_domains' => 'middle.test',
        ]);

        $response = $this->actingAs($user)->get(route('sites.index'));

        $response->assertOk();

        preg_match('/id="pa-sites-index-config">\s*(\{.*?\})\s*<\/script>/s', $response->getContent(), $matches);
        $this->assertNotEmpty($matches[1] ?? null);

        /** @var array{sites: list<array{name: string}>} $config */
        $config = json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);
        $names = array_column($config['sites'], 'name');

        $this->assertSame(['alpha blog', 'Middle Site', 'Zebra Analytics'], $names);
    }

    public function test_creating_site_shows_embed_code_and_instructions(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->post(route('sites.store'), [
            'name' => 'Blog demo',
            'allowed_domains' => 'example.com',
        ]);

        $response->assertRedirect(route('sites.index'));
        $response->assertSessionHas('success');
        $response->assertSessionHas('site_created');

        $followUp = $this->actingAs($user)->get(route('sites.index'));

        $followUp->assertOk();
        $followUp->assertSee('id="pa-site-created-panel"', false);
        $followUp->assertSee(__('Codice di inclusione'), false);
        $followUp->assertSee('Blog demo', false);
        $followUp->assertSee('script async src', false);
    }

    public function test_creating_site_shows_embed_instructions_in_user_locale(): void
    {
        $user = User::factory()->admin()->create(['locale' => 'en']);

        $this->actingAs($user)->post(route('sites.store'), [
            'name' => 'Blog demo',
            'allowed_domains' => 'example.com',
        ]);

        $response = $this->actingAs($user)->get(route('sites.index'));

        $response->assertOk();
        $response->assertSee('Embed code', false);
        $response->assertSee('Site created: Blog demo', false);
        $response->assertSee('Copy the code below.', false);
        $response->assertDontSee('Codice di inclusione', false);
    }

    public function test_sites_index_shows_empty_state_without_sites(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('sites.index'));

        $response->assertOk();
        $response->assertDontSee('id="pa-sites-index-table"', false);
        $response->assertSee('id="createSiteModal"', false);
        $response->assertSee(__('Nessun sito ancora. Creane uno con Nuovo sito.'), false);
    }

    public function test_create_site_validation_errors_reopen_create_modal(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->from(route('sites.index'))->post(route('sites.store'), [
            'name' => '',
            'allowed_domains' => '',
        ]);

        $response->assertRedirect(route('sites.index'));
        $response->assertSessionHasErrors(['name', 'allowed_domains']);
        $response->assertSessionHas('open_create_site_modal', true);

        $followUp = $this->actingAs($user)->get(route('sites.index'));
        $followUp->assertOk();
        $followUp->assertSee('data-pa-open-on-load="1"', false);
        $followUp->assertSee('id="createSiteModal"', false);
    }

    public function test_sites_index_shows_edit_site_labels_in_user_locale(): void
    {
        $user = User::factory()->admin()->create(['locale' => 'en']);

        $user->ownedSites()->create([
            'name' => 'Blog demo',
            'allowed_domains' => 'example.com',
        ]);

        $response = $this->actingAs($user)->get(route('sites.index'));

        $response->assertOk();
        $response->assertSee('Edit site', false);
        $response->assertSee('Save changes', false);
        $response->assertSee('"edit":"Edit"', false);
    }
}
