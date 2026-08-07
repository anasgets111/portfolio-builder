<?php

namespace App\Models;

use Database\Factories\SiteSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

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
 * @property string $site_locale
 * @property bool $is_indexable
 * @property string|null $seo_title
 * @property string|null $seo_description
 * @property string|null $og_image
 * @property string|null $twitter_handle
 * @property array<int, array{platform: string, label: string, url: string}>|null $social_links
 * @property array<string, mixed>|null $appearance
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @phpstan-type AppearanceColors array{canvas: string, panel: string, ink: string, ink_muted: string, brand: string, brand_soft: string}
 * @phpstan-type Appearance array{colors: AppearanceColors, font: string, color_scheme: string, page_width: string, corner_style: string, hero_layout: string, project_layout: string, motion: string}
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
    'site_locale',
    'is_indexable',
    'seo_title',
    'seo_description',
    'og_image',
    'twitter_handle',
    'social_links',
    'appearance',
])]
class SiteSetting extends Model
{
    /** @use HasFactory<SiteSettingFactory> */
    use HasFactory;

    public const array APPEARANCE_OPTIONS = [
        'font' => [
            'poppins' => 'Poppins',
            'inter' => 'Inter',
            'space-mono' => 'Space Mono',
            'playfair-display' => 'Playfair Display',
            'system' => 'System UI',
        ],
        'color_scheme' => [
            'dark' => 'Dark controls',
            'light' => 'Light controls',
        ],
        'page_width' => [
            'standard' => 'Standard',
            'wide' => 'Wide',
        ],
        'corner_style' => [
            'sharp' => 'Sharp',
            'soft' => 'Soft',
        ],
        'hero_layout' => [
            'portrait_right' => 'Portrait right',
            'portrait_left' => 'Portrait left',
        ],
        'project_layout' => [
            'alternating' => 'Alternating',
            'media_left' => 'Images left',
        ],
        'motion' => [
            'standard' => 'Standard',
            'off' => 'Off',
        ],
    ];

    public const array DEFAULT_APPEARANCE = [
        'colors' => [
            'canvas' => '#1e1e2e',
            'panel' => '#313244',
            'ink' => '#cdd6f4',
            'ink_muted' => '#a6adc8',
            'brand' => '#cba6f7',
            'brand_soft' => '#b4befe',
        ],
        'font' => 'poppins',
        'color_scheme' => 'dark',
        'page_width' => 'standard',
        'corner_style' => 'sharp',
        'hero_layout' => 'portrait_right',
        'project_layout' => 'alternating',
        'motion' => 'standard',
    ];

    public const array APPEARANCE_THEME_OPTIONS = [
        'default' => 'Default — bold dark',
        'terminal' => 'Terminal — phosphor monospace',
        'gallery' => 'Gallery — warm art book',
        'editorial' => 'Editorial — high-contrast print',
        'minimal' => 'Minimal — clean neutral',
    ];

    public const array APPEARANCE_COLOR_THEME_OPTIONS = [
        'catppuccin-mocha' => 'Catppuccin Mocha',
        'dracula' => 'Dracula',
        'nord' => 'Nord',
        'gruvbox-dark' => 'Gruvbox Dark',
        'solarized-dark' => 'Solarized Dark',
        'tokyo-night' => 'Tokyo Night',
    ];

    public const array APPEARANCE_COLOR_THEMES = [
        'catppuccin-mocha' => self::DEFAULT_APPEARANCE['colors'],
        'dracula' => [
            'canvas' => '#282a36',
            'panel' => '#44475a',
            'ink' => '#f8f8f2',
            'ink_muted' => '#c6c8d1',
            'brand' => '#ff79c6',
            'brand_soft' => '#8be9fd',
        ],
        'nord' => [
            'canvas' => '#2e3440',
            'panel' => '#3b4252',
            'ink' => '#eceff4',
            'ink_muted' => '#d8dee9',
            'brand' => '#88c0d0',
            'brand_soft' => '#a3be8c',
        ],
        'gruvbox-dark' => [
            'canvas' => '#282828',
            'panel' => '#3c3836',
            'ink' => '#ebdbb2',
            'ink_muted' => '#d5c4a1',
            'brand' => '#fabd2f',
            'brand_soft' => '#b8bb26',
        ],
        'solarized-dark' => [
            'canvas' => '#002b36',
            'panel' => '#073642',
            'ink' => '#fdf6e3',
            'ink_muted' => '#93a1a1',
            'brand' => '#2aa198',
            'brand_soft' => '#b58900',
        ],
        'tokyo-night' => [
            'canvas' => '#1a1b26',
            'panel' => '#24283b',
            'ink' => '#c0caf5',
            'ink_muted' => '#a9b1d6',
            'brand' => '#7aa2f7',
            'brand_soft' => '#bb9af7',
        ],
    ];

    public const array APPEARANCE_THEMES = [
        'default' => self::DEFAULT_APPEARANCE,
        'terminal' => [
            'colors' => [
                'canvas' => '#07130b',
                'panel' => '#0f2415',
                'ink' => '#d6ffd9',
                'ink_muted' => '#9dd8a6',
                'brand' => '#58ff7a',
                'brand_soft' => '#b4ff61',
            ],
            'font' => 'space-mono',
            'color_scheme' => 'dark',
            'page_width' => 'wide',
            'corner_style' => 'sharp',
            'hero_layout' => 'portrait_left',
            'project_layout' => 'media_left',
            'motion' => 'off',
        ],
        'gallery' => [
            ...self::DEFAULT_APPEARANCE,
            'colors' => [
                'canvas' => '#f7f2e8',
                'panel' => '#e8ded0',
                'ink' => '#211d1a',
                'ink_muted' => '#5c5148',
                'brand' => '#8b2f23',
                'brand_soft' => '#6f3f2a',
            ],
            'font' => 'playfair-display',
            'color_scheme' => 'light',
            'page_width' => 'wide',
            'corner_style' => 'soft',
        ],
        'editorial' => [
            ...self::DEFAULT_APPEARANCE,
            'colors' => [
                'canvas' => '#f4f1ea',
                'panel' => '#ded8ce',
                'ink' => '#171717',
                'ink_muted' => '#57534e',
                'brand' => '#9f1239',
                'brand_soft' => '#1e3a5f',
            ],
            'font' => 'playfair-display',
            'color_scheme' => 'light',
            'hero_layout' => 'portrait_left',
            'motion' => 'off',
        ],
        'minimal' => [
            ...self::DEFAULT_APPEARANCE,
            'colors' => [
                'canvas' => '#fafafa',
                'panel' => '#e5e7eb',
                'ink' => '#111827',
                'ink_muted' => '#4b5563',
                'brand' => '#1d4ed8',
                'brand_soft' => '#334155',
            ],
            'font' => 'inter',
            'color_scheme' => 'light',
            'corner_style' => 'soft',
            'project_layout' => 'media_left',
            'motion' => 'off',
        ],
    ];

    private const array FONT_STACKS = [
        'poppins' => 'var(--font-poppins, Poppins), ui-sans-serif, system-ui, sans-serif',
        'inter' => 'var(--font-inter, Inter), ui-sans-serif, system-ui, sans-serif',
        'space-mono' => 'var(--font-space-mono, "Space Mono"), ui-monospace, monospace',
        'playfair-display' => 'var(--font-playfair-display, "Playfair Display"), ui-serif, Georgia, serif',
        'system' => 'ui-sans-serif, system-ui, sans-serif',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'is_singleton' => true,
        'site_locale' => 'en',
        'is_indexable' => false,
    ];

    public static function current(): ?self
    {
        return self::query()->first();
    }

    /** @return Appearance */
    public static function resolveAppearance(mixed $appearance): array
    {
        $appearance = is_array($appearance) ? $appearance : [];
        $rawColors = is_array($appearance['colors'] ?? null) ? $appearance['colors'] : [];
        $defaultColors = self::DEFAULT_APPEARANCE['colors'];
        $colors = [];

        foreach ($defaultColors as $name => $default) {
            $colors[$name] = self::resolvedColor($rawColors, $name, $default);
        }

        if (self::appearanceContrastFailures($colors) !== []) {
            $colors = $defaultColors;
        }

        $resolved = ['colors' => $colors];

        foreach (self::APPEARANCE_OPTIONS as $name => $options) {
            $resolved[$name] = self::allowedOption($appearance[$name] ?? null, $options, self::DEFAULT_APPEARANCE[$name]);
        }

        return $resolved;
    }

    /** @param Appearance $appearance */
    public static function appearanceStyle(array $appearance): string
    {
        $colors = $appearance['colors'];
        $variables = [
            '--color-canvas' => $colors['canvas'],
            '--color-panel' => $colors['panel'],
            '--color-ink' => $colors['ink'],
            '--color-ink-muted' => $colors['ink_muted'],
            '--color-brand' => $colors['brand'],
            '--color-brand-soft' => $colors['brand_soft'],
            '--font-sans' => self::FONT_STACKS[$appearance['font']],
            '--portfolio-page-width' => $appearance['page_width'] === 'wide' ? '96rem' : '80rem',
            '--portfolio-reading-width' => $appearance['page_width'] === 'wide' ? '88rem' : '74rem',
            '--portfolio-surface-radius' => $appearance['corner_style'] === 'soft' ? '1rem' : '0rem',
            '--radius-md' => $appearance['corner_style'] === 'soft' ? '0.75rem' : '0.375rem',
            '--radius-xl' => $appearance['corner_style'] === 'soft' ? '1.5rem' : '0.75rem',
        ];

        return collect($variables)
            ->map(fn (string $value, string $property): string => "{$property}: {$value}")
            ->implode('; ');
    }

    /**
     * @param  array<array-key, mixed>  $colors
     * @return array<int, string>
     */
    public static function appearanceContrastFailures(array $colors): array
    {
        $validatedColors = [];

        foreach (array_keys(self::DEFAULT_APPEARANCE['colors']) as $colorName) {
            $color = $colors[$colorName] ?? null;

            if (! is_string($color) || ! self::isHexColor($color)) {
                return ['Every appearance color must be a six-digit hexadecimal color.'];
            }

            $validatedColors[$colorName] = $color;
        }

        /** @var list<array{string, string, string}> $checks */
        $checks = [
            ['ink', 'canvas', 'Text must have at least 4.5:1 contrast against the page background.'],
            ['ink_muted', 'canvas', 'Muted text must have at least 4.5:1 contrast against the page background.'],
            ['brand', 'canvas', 'The accent must have at least 4.5:1 contrast against the page background.'],
            ['brand_soft', 'canvas', 'The soft accent must have at least 4.5:1 contrast against the page background.'],
            ['ink', 'panel', 'Text must have at least 4.5:1 contrast against panels.'],
        ];
        $failures = [];

        foreach ($checks as [$foreground, $background, $message]) {
            if (self::contrastRatio($validatedColors[$foreground], $validatedColors[$background]) < 4.5) {
                $failures[] = $message;
            }
        }

        return $failures;
    }

    public static function isWebUrl(string $url): bool
    {
        return Str::startsWith($url, ['http://', 'https://'])
            && Str::isUrl($url, ['http', 'https']);
    }

    /**
     * Determine whether a social link points at an approved web, email, or telephone target.
     */
    public static function isSafeSocialLinkUrl(mixed $url): bool
    {
        if (! is_string($url)) {
            return false;
        }

        $isEmailUrl = Str::startsWith($url, 'mailto:')
            && filter_var(Str::after($url, 'mailto:'), FILTER_VALIDATE_EMAIL) !== false;
        $isTelephoneUrl = Str::startsWith($url, 'tel:')
            && preg_match('/^\+?[0-9][0-9(). -]*$/', Str::after($url, 'tel:')) === 1;

        return self::isWebUrl($url) || $isEmailUrl || $isTelephoneUrl;
    }

    /** @param array<string, string> $options */
    private static function allowedOption(mixed $value, array $options, string $default): string
    {
        return is_string($value) && array_key_exists($value, $options) ? $value : $default;
    }

    private static function isHexColor(string $color): bool
    {
        return preg_match('/^#[0-9a-f]{6}$/i', $color) === 1;
    }

    /** @param array<array-key, mixed> $colors */
    private static function resolvedColor(array $colors, string $name, string $default): string
    {
        $color = $colors[$name] ?? null;

        return is_string($color) && self::isHexColor($color)
            ? mb_strtolower($color)
            : $default;
    }

    private static function contrastRatio(string $firstColor, string $secondColor): float
    {
        $firstLuminance = self::relativeLuminance($firstColor);
        $secondLuminance = self::relativeLuminance($secondColor);

        return (max($firstLuminance, $secondLuminance) + 0.05)
            / (min($firstLuminance, $secondLuminance) + 0.05);
    }

    private static function relativeLuminance(string $color): float
    {
        $channels = array_map(
            fn (string $channel): float => hexdec($channel) / 255,
            str_split(mb_substr($color, 1), 2),
        );
        $channels = array_map(
            fn (float $channel): float => $channel <= 0.04045
                ? $channel / 12.92
                : (($channel + 0.055) / 1.055) ** 2.4,
            $channels,
        );

        return (0.2126 * $channels[0]) + (0.7152 * $channels[1]) + (0.0722 * $channels[2]);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_singleton' => 'boolean',
            'is_indexable' => 'boolean',
            'social_links' => 'array',
            'appearance' => 'array',
        ];
    }
}
