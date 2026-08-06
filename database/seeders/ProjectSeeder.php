<?php

namespace Database\Seeders;

use App\Models\Project;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $projects = [
            [
                'title' => 'Project Title',
                'summary' => 'Briefly describe the challenge, your approach, and the outcome of this project.',
                'body' => <<<'HTML'
<p>Use this space to explain the project in more detail. Highlight your role, the decisions you made, and the results you achieved.</p><p>Replace this placeholder with your own project story in the CMS.</p>
HTML,
                'image' => 'projects/project-placeholder.svg',
                'source_url' => null,
                'live_url' => null,
                'technologies' => ['Technology One', 'Technology Two'],
                'sort_order' => 1,
                'is_published' => true,
                'published_at' => null,
            ],
        ];

        foreach ($projects as $project) {
            Project::query()->updateOrCreate(
                ['title' => $project['title']],
                $project,
            );
        }
    }
}
