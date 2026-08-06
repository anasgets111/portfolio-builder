<?php

namespace App\Models;

use Database\Factories\SiteSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property bool $is_singleton
 * @property string|null $name
 * @property string|null $professional_title
 * @property string|null $hero_heading
 * @property string|null $hero_subheading
 * @property string|null $hero_description
 * @property string|null $profile_image
 * @property string|null $about_content
 * @property string|null $contact_content
 * @property string|null $email
 * @property string|null $resume_file
 * @property string|null $site_url
 * @property string|null $seo_title
 * @property string|null $seo_description
 * @property array<int, string>|null $seo_keywords
 * @property string|null $og_image
 * @property string|null $twitter_handle
 * @property array<int, array{platform: string, label: string, url: string}>|null $social_links
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name',
    'professional_title',
    'hero_heading',
    'hero_subheading',
    'hero_description',
    'profile_image',
    'about_content',
    'contact_content',
    'email',
    'resume_file',
    'site_url',
    'seo_title',
    'seo_description',
    'seo_keywords',
    'og_image',
    'twitter_handle',
    'social_links',
])]
class SiteSetting extends Model
{
    /** @use HasFactory<SiteSettingFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = [
        'is_singleton' => true,
    ];

    public static function current(): ?self
    {
        return self::query()->first();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_singleton' => 'boolean',
            'seo_keywords' => 'array',
            'social_links' => 'array',
        ];
    }
}
