<?php

namespace Database\Seeders;

use App\Models\Skill;
use Illuminate\Database\Seeder;

class SkillSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $skills = [
            'Skill One',
            'Skill Two',
            'Skill Three',
        ];

        foreach ($skills as $sortOrder => $name) {
            Skill::query()->updateOrCreate(
                ['name' => $name],
                ['sort_order' => $sortOrder + 1, 'is_published' => true],
            );
        }
    }
}
