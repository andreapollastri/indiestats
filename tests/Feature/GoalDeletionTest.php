<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoalDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_goal_removes_tracking_events_with_same_tag(): void
    {
        $user = User::factory()->create();
        $site = $user->sites()->create([
            'name' => 'Test site',
            'allowed_domains' => 'example.com',
        ]);
        $goal = $site->goals()->create([
            'label' => 'Iscrizione',
            'event_name' => 'signup_complete',
        ]);
        $site->trackingEvents()->create([
            'visitor_id' => 'visitor-a',
            'name' => 'signup_complete',
            'path' => '/',
            'created_at' => now(),
        ]);
        $other = $site->trackingEvents()->create([
            'visitor_id' => 'visitor-b',
            'name' => 'other_tag',
            'path' => '/',
            'created_at' => now(),
        ]);

        $this->actingAs($user);

        $response = $this->delete(route('sites.goals.destroy', [
            'site' => $site->public_key,
            'goal' => $goal->id,
        ]));

        $response->assertRedirect();
        $this->assertDatabaseMissing('goals', ['id' => $goal->id]);
        $this->assertDatabaseMissing('tracking_events', ['name' => 'signup_complete', 'site_id' => $site->id]);
        $this->assertDatabaseHas('tracking_events', ['id' => $other->id]);
    }
}
