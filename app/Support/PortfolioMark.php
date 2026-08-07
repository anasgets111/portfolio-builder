<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Support\Str;

final class PortfolioMark
{
    public function __construct(private readonly ?SiteSetting $siteSetting) {}

    public function initials(): string
    {
        $initials = Str::of($this->siteSetting?->name ?: 'Portfolio')
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => Str::substr($part, 0, 1))
            ->implode('');

        return $initials !== '' ? $initials : 'P';
    }

    /** @return array{canvas: string, panel: string, ink: string, ink_muted: string, brand: string, brand_soft: string} */
    public function colors(): array
    {
        return SiteSetting::resolveAppearance($this->siteSetting?->appearance)['colors'];
    }

    public function fingerprint(): string
    {
        return mb_substr(hash('sha256', $this->initials().serialize($this->colors())), 0, 12);
    }
}
