<?php

namespace Database\Factories;

use App\Models\Goal;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Goal>
 */
class GoalFactory extends Factory
{
    private const GOALS = [
        ['label' => 'Registrazioni', 'event_name' => 'signup'],
        ['label' => 'Acquisti', 'event_name' => 'purchase'],
        ['label' => 'Iscrizioni newsletter', 'event_name' => 'newsletter_subscribe'],
        ['label' => 'Download', 'event_name' => 'download'],
        ['label' => 'Modulo contatto', 'event_name' => 'contact_form'],
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $goal = fake()->randomElement(self::GOALS);

        return [
            'site_id' => Site::factory(),
            'label' => $goal['label'],
            'event_name' => $goal['event_name'],
        ];
    }
}
