<?php

namespace App\Models;

use Database\Factories\ExperienceFactory;
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
 * @property string $company
 * @property string $position
 * @property Carbon $start_date
 * @property Carbon|null $end_date
 * @property string $location
 * @property string $description
 * @property array<int, string> $technologies
 * @property int $sort_order
 * @property bool $is_published
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Project> $projects
 * @property-read Collection<int, Project> $publishedProjects
 */
#[Fillable([
    'company',
    'position',
    'start_date',
    'end_date',
    'location',
    'description',
    'technologies',
    'sort_order',
    'is_published',
])]
class Experience extends Model
{
    /** @use HasFactory<ExperienceFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = [
        'sort_order' => 0,
        'is_published' => false,
    ];

    /**
     * @param  Builder<Experience>  $query
     * @return Builder<Experience>
     */
    #[Scope]
    protected function published(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * @param  Builder<Experience>  $query
     * @return Builder<Experience>
     */
    #[Scope]
    protected function ordered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /** @return BelongsToMany<Project, $this> */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class);
    }

    /** @return BelongsToMany<Project, $this> */
    public function publishedProjects(): BelongsToMany
    {
        $relationship = $this->projects();

        $relationship->getQuery()->published()->ordered();

        return $relationship;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'technologies' => 'array',
            'sort_order' => 'integer',
            'is_published' => 'boolean',
        ];
    }
}
