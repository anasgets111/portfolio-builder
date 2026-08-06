<?php

namespace App\Filament;

use App\Models\Experience;
use App\Models\Project;
use App\Models\Skill;
use Illuminate\Support\Str;

class TechnologySuggestions
{
    /** @return array<int, string> */
    public static function all(): array
    {
        $technologies = Skill::query()->pluck('name');

        $technologies->push(
            ...Experience::query()->pluck('technologies')->flatten(),
            ...Project::query()->pluck('technologies')->flatten(),
        );

        return $technologies
            ->filter(fn (mixed $technology): bool => is_string($technology))
            ->map(fn (string $technology): string => trim($technology))
            ->filter()
            ->unique(fn (string $technology): string => Str::lower($technology))
            ->sortBy(fn (string $technology): string => Str::lower($technology), SORT_NATURAL)
            ->values()
            ->all();
    }
}
