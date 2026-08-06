<?php

namespace App\Models;

use Database\Factories\AnalyticsEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $target
 * @property int|null $value
 * @property int|null $interaction_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name',
    'target',
    'value',
])]
class AnalyticsEvent extends Model
{
    /** @var array<int, string> */
    public const array TRACKABLE_NAMES = [
        'section_viewed',
        'section_engaged',
        'navigation_clicked',
        'project_opened',
        'project_hovered',
        'project_link_clicked',
        'cv_opened',
        'contact_clicked',
        'social_clicked',
    ];

    /** @use HasFactory<AnalyticsEventFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'value' => 'integer',
        ];
    }
}
