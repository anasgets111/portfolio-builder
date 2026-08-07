<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class PortfolioMediaSeeder extends Seeder
{
    /** @var list<string> */
    public const PLACEHOLDER_IMAGES = [
        'site/profile-images/profile-placeholder.svg',
        'projects/project-placeholder.svg',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::PLACEHOLDER_IMAGES as $image) {
            if (Storage::disk('public')->exists($image)) {
                continue;
            }

            Storage::disk('public')->put(
                $image,
                File::get(database_path('seeders/assets/portfolio/'.$image)),
            );
        }
    }
}
