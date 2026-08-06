<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SiteSetting::query()->updateOrCreate(
            ['is_singleton' => true],
            [
                'name' => 'Your Name',
                'professional_title' => 'Your Professional Title',
                'hero_heading' => 'Hello, I\'m Your Name.',
                'hero_subheading' => 'Your Professional Title',
                'hero_description' => 'Use this portfolio to share your work, experience, and the skills you bring to each project.',
                'profile_image' => 'site/profile-images/profile-placeholder.svg',
                'about_content' => <<<'HTML'
<p>Introduce yourself here. Share your background, the kind of work you do, and what sets your approach apart.</p><p>Use the CMS to replace this placeholder copy with your own story.</p>
HTML,
                'contact_content' => '<p>Invite visitors to get in touch. Add the contact details and social links you want to share in the CMS.</p>',
                'email' => 'hello@example.com',
                'resume_file' => null,
                'site_url' => null,
                'seo_title' => 'Your Name | Portfolio',
                'seo_description' => 'A portfolio showcasing your work, experience, and skills.',
                'seo_keywords' => [
                    'Portfolio',
                    'Your Industry',
                    'Your Specialty',
                ],
                'og_image' => 'site/profile-images/profile-placeholder.svg',
                'twitter_handle' => null,
                'social_links' => [
                    ['platform' => 'LinkedIn', 'label' => 'LinkedIn', 'url' => 'https://www.linkedin.com/in/your-profile'],
                    ['platform' => 'GitHub', 'label' => 'GitHub', 'url' => 'https://github.com/your-username'],
                    ['platform' => 'Email', 'label' => 'Email', 'url' => 'mailto:hello@example.com'],
                ],
            ],
        );
    }
}
