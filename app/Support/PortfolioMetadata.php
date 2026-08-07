<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class PortfolioMetadata
{
    public function __construct(private readonly ?SiteSetting $siteSetting) {}

    public function title(): string
    {
        return $this->filledString($this->siteSetting?->seo_title)
            ?? $this->filledString($this->siteSetting?->name)
            ?? $this->applicationName();
    }

    public function siteName(): string
    {
        return $this->filledString($this->siteSetting?->name) ?? $this->applicationName();
    }

    public function description(): ?string
    {
        $description = $this->filledString($this->siteSetting?->seo_description)
            ?? $this->filledString($this->siteSetting?->hero_description);

        return $description === null ? null : Str::squish(strip_tags($description));
    }

    public function locale(): string
    {
        $locale = $this->filledString($this->siteSetting?->site_locale)
            ?? str_replace('_', '-', app()->getLocale());

        return preg_match('/^[A-Za-z]{2,3}(?:-[A-Za-z0-9]{2,8})*$/', $locale) === 1
            ? $locale
            : 'en';
    }

    public function openGraphLocale(): string
    {
        return str_replace('-', '_', $this->locale());
    }

    public static function configuredApplicationUrl(): ?string
    {
        $url = config('app.url');

        if (! is_string($url)) {
            return null;
        }

        $url = trim($url);

        if (! self::isApplicationOrigin($url)) {
            return null;
        }

        return rtrim($url, '/');
    }

    private static function isApplicationOrigin(string $value): bool
    {
        if (! SiteSetting::isWebUrl($value)) {
            return false;
        }

        $parts = parse_url($value);
        $path = is_array($parts) ? ($parts['path'] ?? '') : '';

        return is_array($parts)
            && isset($parts['host'])
            && ($path === '' || $path === '/')
            && ! isset($parts['query'])
            && ! isset($parts['fragment']);
    }

    public function canonicalUrl(string $path = '/'): ?string
    {
        $applicationUrl = self::configuredApplicationUrl();

        if ($applicationUrl === null) {
            return null;
        }

        $relativePath = trim($path, '/');

        return $relativePath === '' ? $applicationUrl : "{$applicationUrl}/{$relativePath}";
    }

    public function isIndexable(): bool
    {
        return (bool) $this->siteSetting?->is_indexable && self::configuredApplicationUrl() !== null;
    }

    public function robotsDirective(): string
    {
        return $this->isIndexable() ? 'index, follow' : 'noindex, nofollow';
    }

    public function twitterHandle(): ?string
    {
        $handle = $this->filledString($this->siteSetting?->twitter_handle);

        return $handle === null ? null : '@'.ltrim($handle, '@');
    }

    /** @return array{url: string, alt: string}|null */
    public function openGraphImage(): ?array
    {
        $path = $this->filledString($this->siteSetting?->og_image);
        $url = $this->mediaUrl($path);

        if ($path === null || $url === null) {
            return null;
        }

        return [
            'url' => $url,
            'alt' => $this->siteName().' portfolio preview',
        ];
    }

    /** @return array<string, mixed>|null */
    public function structuredData(): ?array
    {
        $canonicalUrl = $this->canonicalUrl();

        if ($canonicalUrl === null) {
            return null;
        }

        $websiteId = $canonicalUrl.'#website';
        $graph = [[
            '@type' => 'WebSite',
            '@id' => $websiteId,
            'url' => $canonicalUrl,
            'name' => $this->siteName(),
            'inLanguage' => $this->locale(),
        ]];
        $name = $this->filledString($this->siteSetting?->name);

        if ($name === null) {
            return [
                '@context' => 'https://schema.org',
                '@graph' => $graph,
            ];
        }

        $personId = $canonicalUrl.'#person';
        $profileId = $canonicalUrl.'#profile';
        $person = array_filter([
            '@type' => 'Person',
            '@id' => $personId,
            'name' => $name,
            'jobTitle' => $this->filledString($this->siteSetting?->professional_title),
            'description' => $this->description(),
            'image' => $this->mediaUrl($this->filledString($this->siteSetting?->profile_image)),
            'url' => $canonicalUrl,
            'sameAs' => $this->socialProfiles(),
        ], fn (mixed $value): bool => $value !== null && $value !== '' && $value !== []);
        $profile = array_filter([
            '@type' => 'ProfilePage',
            '@id' => $profileId,
            'url' => $canonicalUrl,
            'name' => $this->title(),
            'description' => $this->description(),
            'inLanguage' => $this->locale(),
            'isPartOf' => ['@id' => $websiteId],
            'mainEntity' => ['@id' => $personId],
        ], fn (mixed $value): bool => $value !== null && $value !== '');

        return [
            '@context' => 'https://schema.org',
            '@graph' => [...$graph, $profile, $person],
        ];
    }

    /** @return list<string> */
    private function socialProfiles(): array
    {
        $socialLinks = $this->siteSetting === null ? [] : ($this->siteSetting->social_links ?? []);
        $profiles = [];

        foreach ($socialLinks as $socialLink) {
            if (SiteSetting::isWebUrl($socialLink['url'])) {
                $profiles[] = $socialLink['url'];
            }
        }

        return array_values(array_unique($profiles));
    }

    private function mediaUrl(?string $path): ?string
    {
        if ($path === null) {
            return null;
        }

        /** @var FilesystemAdapter $publicDisk */
        $publicDisk = Storage::disk('public');
        $publicUrl = $publicDisk->url($path);
        $origin = self::configuredApplicationUrl();

        if ($origin === null) {
            return Str::startsWith($publicUrl, ['http://', 'https://'])
                ? $publicUrl
                : url($publicUrl);
        }

        $publicPath = parse_url($publicUrl, PHP_URL_PATH);

        return $origin.'/'.ltrim(is_string($publicPath) ? $publicPath : $publicUrl, '/');
    }

    private function filledString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function applicationName(): string
    {
        return $this->filledString(config('app.name')) ?? 'Portfolio';
    }
}
