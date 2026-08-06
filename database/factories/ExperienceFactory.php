<?php

namespace Database\Factories;

use App\Models\Experience;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Experience>
 */
class ExperienceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-8 years', '-1 year');

        return [
            'company' => fake()->company(),
            'position' => fake()->jobTitle(),
            'start_date' => $startDate,
            'end_date' => fake()->optional()->dateTimeBetween($startDate, 'now'),
            'location' => fake()->city().', '.fake()->country(),
            'description' => fake()->paragraph(),
            'technologies' => fake()->randomElements(
                ['Laravel', 'Livewire', 'Filament', 'PHP', 'MySQL', 'JavaScript'],
                fake()->numberBetween(2, 4),
            ),
            'sort_order' => fake()->numberBetween(0, 100),
            'is_published' => false,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_published' => true,
        ]);
    }
}
