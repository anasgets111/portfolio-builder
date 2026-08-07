<?php

namespace Database\Factories;

use App\Models\SiteSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SiteSetting>
 */
class SiteSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'professional_title' => fake()->jobTitle(),
            'hero_heading' => fake()->sentence(4),
            'hero_subheading' => fake()->sentence(6),
            'hero_description' => fake()->paragraph(),
            'about_content' => fake()->paragraphs(3, true),
            'contact_content' => fake()->paragraph(),
            'email' => fake()->safeEmail(),
            'site_url' => fake()->url(),
            'seo_title' => fake()->sentence(5),
            'seo_description' => fake()->sentence(12),
            'seo_keywords' => fake()->words(5),
            'twitter_handle' => fake()->userName(),
            'social_links' => [[
                'platform' => 'GitHub',
                'label' => 'GitHub',
                'url' => fake()->url(),
            ]],
            'appearance' => SiteSetting::DEFAULT_APPEARANCE,
        ];
    }
}
