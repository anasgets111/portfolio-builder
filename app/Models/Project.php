<?php

namespace App\Models;

use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $title
 * @property string $summary
 * @property string $body
 * @property string $image
 * @property string|null $source_url
 * @property string|null $live_url
 * @property array<int, string> $technologies
 * @property int $sort_order
 * @property bool $is_published
 * @property Carbon|null $published_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Experience> $experiences
 * @property-read Collection<int, Experience> $publishedExperiences
 */
#[Fillable([
    'title',
    'summary',
    'body',
    'image',
    'source_url',
    'live_url',
    'technologies',
    'sort_order',
    'is_published',
    'published_at',
])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = [
        'sort_order' => 0,
        'is_published' => false,
    ];

    /**
     * @param  Builder<Project>  $query
     * @return Builder<Project>
     */
    #[Scope]
    protected function published(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * @param  Builder<Project>  $query
     * @return Builder<Project>
     */
    #[Scope]
    protected function ordered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /** @return BelongsToMany<Experience, $this> */
    public function experiences(): BelongsToMany
    {
        return $this->belongsToMany(Experience::class);
    }

    /** @return BelongsToMany<Experience, $this> */
    public function publishedExperiences(): BelongsToMany
    {
        $relationship = $this->experiences();

        $relationship->getQuery()->published()->ordered();

        return $relationship;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'technologies' => 'array',
            'sort_order' => 'integer',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }
}
