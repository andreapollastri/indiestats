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
        $response->assertSee(__('Domini consentiti'), false);
        $response->assertSee('Blog demo', false);
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
        $response->assertSee(__('Nessun sito ancora. Creane uno qui sopra.'), false);
    }
}
