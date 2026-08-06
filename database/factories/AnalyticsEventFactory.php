<?php

namespace Database\Factories;

use App\Models\AnalyticsEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnalyticsEvent>
 */
class AnalyticsEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(AnalyticsEvent::TRACKABLE_NAMES),
            'target' => fake()->randomElement(['about', 'projects', 'experience', 'contact']),
            'value' => fake()->optional()->numberBetween(1_000, 120_000),
        ];
    }
}
