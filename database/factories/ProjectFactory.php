<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->unique()->sentence(3),
            'summary' => fake()->sentence(12),
            'body' => fake()->paragraphs(3, true),
            'image' => 'projects/'.fake()->uuid().'.jpg',
            'source_url' => fake()->optional()->url(),
            'live_url' => fake()->optional()->url(),
            'technologies' => fake()->randomElements(
                ['Laravel', 'Livewire', 'Filament', 'PHP', 'MySQL', 'JavaScript'],
                fake()->numberBetween(2, 4),
            ),
            'sort_order' => fake()->numberBetween(0, 100),
            'is_published' => false,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_published' => true,
            'published_at' => now(),
        ]);
    }
}
