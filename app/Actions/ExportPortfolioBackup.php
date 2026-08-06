<?php

namespace App\Actions;

use App\Models\Experience;
use App\Models\Project;
use App\Models\SiteSetting;
use App\Models\Skill;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use ZipArchive;

class ExportPortfolioBackup
{
    public const int FORMAT_VERSION = 1;

    public const string MANIFEST_PATH = 'portfolio-backup.json';

    public const array SITE_SETTING_FIELDS = [
        'name',
        'professional_title',
        'hero_heading',
        'hero_subheading',
        'hero_description',
        'profile_image',
        'about_content',
        'contact_content',
        'email',
        'resume_file',
        'site_url',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'og_image',
        'twitter_handle',
        'social_links',
    ];

    public const array PROJECT_FIELDS = [
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
    ];

    public const array EXPERIENCE_FIELDS = [
        'company',
        'position',
        'start_date',
        'end_date',
        'location',
        'description',
        'technologies',
        'sort_order',
        'is_published',
    ];

    public const array SKILL_FIELDS = [
        'name',
        'sort_order',
        'is_published',
    ];

    public function handle(): string
    {
        $siteSetting = SiteSetting::current()
            ?? throw new RuntimeException('Create the site settings record before exporting a backup.');
        $projects = Project::query()->ordered()->get();
        $experiences = Experience::query()
            ->ordered()
            ->with(['projects' => fn ($query) => $query->ordered()])
            ->get();
        $skills = Skill::query()->ordered()->get();
        $publicDisk = Storage::disk('public');
        $privateDisk = Storage::disk('local');
        $relativeArchivePath = 'portfolio-backups/'.Str::uuid().'.zip';

        $privateDisk->makeDirectory('portfolio-backups');

        $archivePath = $privateDisk->path($relativeArchivePath);
        $zip = new ZipArchive;

        if ($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('The portfolio backup archive could not be created.');
        }

        try {
            $media = $this->addMedia(
                $zip,
                $publicDisk,
                $this->referencedMediaPaths($siteSetting, $projects->all()),
            );

            $manifest = [
                'format_version' => self::FORMAT_VERSION,
                'exported_at' => now()->toIso8601String(),
                'site_setting' => Arr::only($siteSetting->toArray(), self::SITE_SETTING_FIELDS),
                'projects' => $projects
                    ->map(fn (Project $project): array => [
                        'key' => (string) $project->id,
                        ...Arr::only($project->toArray(), self::PROJECT_FIELDS),
                        'published_at' => $project->published_at?->toIso8601String(),
                    ])
                    ->all(),
                'experiences' => $experiences
                    ->map(fn (Experience $experience): array => [
                        ...Arr::only($experience->toArray(), self::EXPERIENCE_FIELDS),
                        'start_date' => $experience->start_date->toDateString(),
                        'end_date' => $experience->end_date?->toDateString(),
                        'project_keys' => $experience->projects
                            ->map(fn (Project $project): string => (string) $project->id)
                            ->values()
                            ->all(),
                    ])
                    ->all(),
                'skills' => $skills
                    ->map(fn (Skill $skill): array => Arr::only($skill->toArray(), self::SKILL_FIELDS))
                    ->all(),
                'media' => $media,
            ];

            $manifestJson = json_encode(
                $manifest,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
            );

            if (! $zip->addFromString(self::MANIFEST_PATH, $manifestJson)) {
                throw new RuntimeException('The portfolio backup manifest could not be written.');
            }

            if (! $zip->close()) {
                throw new RuntimeException('The portfolio backup archive could not be finalized.');
            }

            return $archivePath;
        } catch (Throwable $exception) {
            $zip->close();
            $privateDisk->delete($relativeArchivePath);

            throw $exception;
        }
    }

    /**
     * @param  array<int, string>  $paths
     * @return array<int, array{path: string, sha256: string, bytes: int}>
     */
    private function addMedia(ZipArchive $zip, FilesystemAdapter $disk, array $paths): array
    {
        $media = [];

        foreach ($paths as $path) {
            $this->assertManagedMediaPath($path);

            if (! $disk->exists($path)) {
                throw new RuntimeException("Referenced portfolio media is missing: {$path}");
            }

            $contents = $disk->get($path);

            if (! is_string($contents)) {
                throw new RuntimeException("Referenced portfolio media could not be read: {$path}");
            }

            if (! $zip->addFromString('media/'.$path, $contents)) {
                throw new RuntimeException("Portfolio media could not be archived: {$path}");
            }

            $media[] = [
                'path' => $path,
                'sha256' => hash('sha256', $contents),
                'bytes' => strlen($contents),
            ];
        }

        return $media;
    }

    /**
     * @param  array<int, Project>  $projects
     * @return array<int, string>
     */
    private function referencedMediaPaths(SiteSetting $siteSetting, array $projects): array
    {
        $paths = [
            $siteSetting->profile_image,
            $siteSetting->resume_file,
            $siteSetting->og_image,
            ...array_map(fn (Project $project): string => $project->image, $projects),
        ];

        return array_values(array_unique(array_filter(
            $paths,
            fn (mixed $path): bool => is_string($path) && $path !== '',
        )));
    }

    private function assertManagedMediaPath(string $path): void
    {
        $hasUnsafeSegments = Str::startsWith($path, ['/'])
            || Str::contains($path, ['\\', "\0"])
            || in_array('..', explode('/', $path), true);
        $isManaged = Str::startsWith($path, [
            'projects/',
            'site/profile-images/',
            'site/resumes/',
            'site/seo/',
        ]);

        if ($hasUnsafeSegments || ! $isManaged) {
            throw new RuntimeException("Portfolio media uses an unmanaged path: {$path}");
        }
    }
}
