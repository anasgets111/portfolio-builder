<?php

namespace Database\Seeders;

use App\Models\Experience;
use App\Models\Project;
use Illuminate\Database\Seeder;

class ExperienceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $experience = Experience::query()->updateOrCreate(
            ['company' => 'Company Name'],
            [
                'position' => 'Job Title',
                'start_date' => '2024-01-01',
                'end_date' => null,
                'location' => 'City, Country',
                'description' => 'Summarize your responsibilities, achievements, and the impact of this role.',
                'technologies' => ['Technology One', 'Technology Two'],
                'sort_order' => 1,
                'is_published' => true,
            ],
        );

        $project = Project::query()->where('title', 'Project Title')->firstOrFail();

        $experience->projects()->syncWithoutDetaching([$project->getKey()]);
    }
}
