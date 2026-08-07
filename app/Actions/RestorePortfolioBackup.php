<?php

namespace App\Actions;

use App\Models\Experience;
use App\Models\Project;
use App\Models\SiteSetting;
use App\Models\Skill;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as LaravelValidator;
use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * @phpstan-type PortfolioManifest array{
 *     site_setting: array<string, mixed>,
 *     projects: list<array{key: string, image: string, attributes: array<string, mixed>}>,
 *     experiences: list<array{project_keys: list<string>, attributes: array<string, mixed>}>,
 *     skills: list<array<string, mixed>>,
 *     media: list<array{path: string, sha256: string, bytes: int}>,
 * }
 */
class RestorePortfolioBackup
{
    public const int MAX_ARCHIVE_KILOBYTES = 102_400;

    private const int MAX_ARCHIVE_BYTES = self::MAX_ARCHIVE_KILOBYTES * 1024;

    private const int MAX_UNCOMPRESSED_BYTES = 262_144_000;

    private const int MAX_ENTRIES = 500;

    private const array BUILT_IN_PLACEHOLDERS = [
        'projects/project-placeholder.svg',
        'site/profile-images/profile-placeholder.svg',
    ];

    private const array SITE_SETTING_MEDIA_FIELDS = [
        'profile_image',
        'resume_file',
        'og_image',
    ];

    public function handle(string $archivePath): void
    {
        if (! is_file($archivePath) || filesize($archivePath) > self::MAX_ARCHIVE_BYTES) {
            throw new RuntimeException('The portfolio backup is missing or exceeds the 100 MB limit.');
        }

        $zip = new ZipArchive;

        if ($zip->open($archivePath) !== true) {
            throw new RuntimeException('The uploaded file is not a readable ZIP archive.');
        }

        try {
            $entryNames = $this->validateArchiveEntries($zip);
            $manifest = $this->readManifest($zip);
            $validated = $this->validateManifest($manifest);
            $mediaContents = $this->validateMedia($zip, $entryNames, $validated);
        } finally {
            $zip->close();
        }

        $createdPaths = [];

        try {
            $mediaPathMap = $this->storeMedia($mediaContents, $createdPaths);
            $this->replacePortfolio($validated, $mediaPathMap);
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($createdPaths);

            throw $exception;
        }
    }

    /** @return array<int, string> */
    private function validateArchiveEntries(ZipArchive $zip): array
    {
        if ($zip->numFiles < 1 || $zip->numFiles > self::MAX_ENTRIES) {
            throw new RuntimeException('The portfolio backup contains an invalid number of files.');
        }

        $entryNames = [];
        $totalBytes = 0;

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entry = $zip->statIndex($index);

            if (! is_array($entry)) {
                throw new RuntimeException('The portfolio backup contains an unreadable file entry.');
            }

            $name = $entry['name'];
            $segments = explode('/', $name);
            $hasUnsafePath = $name === ''
                || Str::startsWith($name, ['/'])
                || Str::contains($name, ['\\', "\0"])
                || in_array('..', $segments, true)
                || in_array('.', $segments, true);

            if ($hasUnsafePath) {
                throw new RuntimeException("The portfolio backup contains an unsafe path: {$name}");
            }

            if ($name !== ExportPortfolioBackup::MANIFEST_PATH && ! Str::startsWith($name, 'media/')) {
                throw new RuntimeException("The portfolio backup contains an unexpected file: {$name}");
            }

            if (isset($entryNames[$name])) {
                throw new RuntimeException("The portfolio backup contains a duplicate file: {$name}");
            }

            $totalBytes += (int) $entry['size'];

            if ($totalBytes > self::MAX_UNCOMPRESSED_BYTES) {
                throw new RuntimeException('The portfolio backup expands beyond the 250 MB limit.');
            }

            $entryNames[$name] = true;
        }

        if (! isset($entryNames[ExportPortfolioBackup::MANIFEST_PATH])) {
            throw new RuntimeException('The portfolio backup manifest is missing.');
        }

        return array_keys($entryNames);
    }

    /** @return array<array-key, mixed> */
    private function readManifest(ZipArchive $zip): array
    {
        $json = $zip->getFromName(ExportPortfolioBackup::MANIFEST_PATH);

        if (! is_string($json)) {
            throw new RuntimeException('The portfolio backup manifest could not be read.');
        }

        try {
            $manifest = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            throw new RuntimeException('The portfolio backup manifest is not valid JSON.', previous: $exception);
        }

        if (! is_array($manifest)) {
            throw new RuntimeException('The portfolio backup manifest has an invalid structure.');
        }

        if (($manifest['format_version'] ?? null) !== ExportPortfolioBackup::FORMAT_VERSION) {
            throw new RuntimeException('This portfolio backup format is not supported.');
        }

        return $manifest;
    }

    /**
     * @param  array<array-key, mixed>  $manifest
     * @return PortfolioManifest
     */
    private function validateManifest(array $manifest): array
    {
        $appearanceRules = [];

        foreach (SiteSetting::APPEARANCE_OPTIONS as $name => $options) {
            $appearanceRules["site_setting.appearance.{$name}"] = ['required_with:site_setting.appearance', Rule::in(array_keys($options))];
        }

        $validator = Validator::make($manifest, [
            'format_version' => ['required', 'integer'],
            'exported_at' => ['required', 'date'],
            'site_setting' => ['required', 'array:'.implode(',', ExportPortfolioBackup::SITE_SETTING_FIELDS)],
            'site_setting.name' => ['nullable', 'string', 'max:255'],
            'site_setting.professional_title' => ['nullable', 'string', 'max:255'],
            'site_setting.hero_heading' => ['nullable', 'string', 'max:255'],
            'site_setting.hero_subheading' => ['nullable', 'string', 'max:255'],
            'site_setting.hero_description' => ['nullable', 'string'],
            'site_setting.profile_image' => ['nullable', 'string'],
            'site_setting.about_content' => ['nullable', 'string'],
            'site_setting.contact_content' => ['nullable', 'string'],
            'site_setting.email' => ['nullable', 'email', 'max:255'],
            'site_setting.resume_file' => ['nullable', 'string'],
            'site_setting.site_locale' => ['required', 'string', 'max:35', 'regex:/^[A-Za-z]{2,3}(?:-[A-Za-z0-9]{2,8})*$/'],
            'site_setting.is_indexable' => ['required', 'boolean'],
            'site_setting.seo_title' => ['nullable', 'string', 'max:255'],
            'site_setting.seo_description' => ['nullable', 'string'],
            'site_setting.og_image' => ['nullable', 'string'],
            'site_setting.twitter_handle' => ['nullable', 'string', 'max:255'],
            'site_setting.social_links' => ['nullable', 'array'],
            'site_setting.social_links.*' => ['array:platform,label,url'],
            'site_setting.social_links.*.platform' => ['required', 'string', 'max:100'],
            'site_setting.social_links.*.label' => ['required', 'string', 'max:100'],
            'site_setting.social_links.*.url' => ['required', 'string', 'max:2048'],
            'site_setting.appearance' => ['nullable', 'array:colors,font,color_scheme,page_width,corner_style,hero_layout,project_layout,motion'],
            'site_setting.appearance.colors' => ['required_with:site_setting.appearance', 'array:canvas,panel,ink,ink_muted,brand,brand_soft', 'required_array_keys:canvas,panel,ink,ink_muted,brand,brand_soft'],
            'site_setting.appearance.colors.*' => ['regex:/^#[0-9a-f]{6}$/i'],
            ...$appearanceRules,
            'projects' => ['required', 'array', 'max:1000'],
            'projects.*' => ['array:key,'.implode(',', ExportPortfolioBackup::PROJECT_FIELDS)],
            'projects.*.key' => ['required', 'string', 'distinct'],
            'projects.*.title' => ['required', 'string', 'max:255'],
            'projects.*.summary' => ['required', 'string'],
            'projects.*.body' => ['required', 'string'],
            'projects.*.image' => ['required', 'string'],
            'projects.*.source_url' => ['nullable', 'url:http,https', 'max:2048'],
            'projects.*.live_url' => ['nullable', 'url:http,https', 'max:2048'],
            'projects.*.technologies' => ['required', 'array', 'min:1'],
            'projects.*.technologies.*' => ['string', 'max:255'],
            'projects.*.sort_order' => ['required', 'integer', 'min:0'],
            'projects.*.is_published' => ['required', 'boolean'],
            'projects.*.published_at' => ['nullable', 'date'],
            'experiences' => ['required', 'array', 'max:1000'],
            'experiences.*' => ['array:project_keys,'.implode(',', ExportPortfolioBackup::EXPERIENCE_FIELDS)],
            'experiences.*.company' => ['required', 'string', 'max:255'],
            'experiences.*.position' => ['required', 'string', 'max:255'],
            'experiences.*.start_date' => ['required', 'date_format:Y-m-d'],
            'experiences.*.end_date' => ['nullable', 'date_format:Y-m-d'],
            'experiences.*.location' => ['required', 'string', 'max:255'],
            'experiences.*.description' => ['required', 'string'],
            'experiences.*.technologies' => ['required', 'array', 'min:1'],
            'experiences.*.technologies.*' => ['string', 'max:255'],
            'experiences.*.sort_order' => ['required', 'integer', 'min:0'],
            'experiences.*.is_published' => ['required', 'boolean'],
            'experiences.*.project_keys' => ['present', 'array'],
            'experiences.*.project_keys.*' => ['string'],
            'skills' => ['required', 'array', 'max:1000'],
            'skills.*' => ['array:'.implode(',', ExportPortfolioBackup::SKILL_FIELDS)],
            'skills.*.name' => ['required', 'string', 'max:255', 'distinct'],
            'skills.*.sort_order' => ['required', 'integer', 'min:0'],
            'skills.*.is_published' => ['required', 'boolean'],
            'media' => ['required', 'array', 'max:'.self::MAX_ENTRIES],
            'media.*' => ['array:path,sha256,bytes'],
            'media.*.path' => ['required', 'string', 'distinct'],
            'media.*.sha256' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/'],
            'media.*.bytes' => ['required', 'integer', 'min:1'],
        ]);

        $validator->after(function (LaravelValidator $validator): void {
            $appearance = data_get($validator->getData(), 'site_setting.appearance');
            if (! is_array($appearance) || ! is_array($appearance['colors'] ?? null)) {
                return;
            }

            foreach (SiteSetting::appearanceContrastFailures($appearance['colors']) as $failure) {
                $validator->errors()->add('site_setting.appearance.colors', $failure);
            }
        });

        $validated = $validator->validate();

        $siteSetting = $this->manifestFields(
            Arr::array($validated, 'site_setting'),
            ExportPortfolioBackup::SITE_SETTING_FIELDS,
        );
        $siteSetting['appearance'] = SiteSetting::resolveAppearance($siteSetting['appearance'] ?? null);
        $projects = [];
        $experiences = [];
        $skills = [];
        $media = [];

        foreach ($this->manifestRows($validated['projects'] ?? null, 'projects') as $project) {
            $projects[] = [
                'key' => Arr::string($project, 'key'),
                'image' => Arr::string($project, 'image'),
                'attributes' => $this->manifestFields($project, ExportPortfolioBackup::PROJECT_FIELDS),
            ];
        }

        foreach ($this->manifestRows($validated['experiences'] ?? null, 'experiences') as $experience) {
            $experiences[] = [
                'project_keys' => $this->manifestStrings($experience, 'project_keys'),
                'attributes' => $this->manifestFields($experience, ExportPortfolioBackup::EXPERIENCE_FIELDS),
            ];
        }

        foreach ($this->manifestRows($validated['skills'] ?? null, 'skills') as $skill) {
            $skills[] = $this->manifestFields($skill, ExportPortfolioBackup::SKILL_FIELDS);
        }

        foreach ($this->manifestRows($validated['media'] ?? null, 'media') as $entry) {
            $media[] = [
                'path' => Arr::string($entry, 'path'),
                'sha256' => Arr::string($entry, 'sha256'),
                'bytes' => Arr::integer($entry, 'bytes'),
            ];
        }

        $projectKeys = array_column($projects, 'key');

        foreach ($experiences as $experience) {
            if (array_diff($experience['project_keys'], $projectKeys) !== []) {
                throw new RuntimeException('The portfolio backup contains an unknown project relationship.');
            }
        }

        foreach ($this->manifestRows($siteSetting['social_links'] ?? [], 'social_links') as $socialLink) {
            if (! SiteSetting::isSafeSocialLinkUrl(Arr::string($socialLink, 'url'))) {
                throw new RuntimeException('The portfolio backup contains an unsafe social link.');
            }
        }

        return [
            'site_setting' => $siteSetting,
            'projects' => $projects,
            'experiences' => $experiences,
            'skills' => $skills,
            'media' => $media,
        ];
    }

    /** @return list<array<array-key, mixed>> */
    private function manifestRows(mixed $rows, string $field): array
    {
        if (! is_array($rows)) {
            throw new RuntimeException("The portfolio backup manifest has an invalid \"{$field}\" list.");
        }

        $entries = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                throw new RuntimeException("The portfolio backup manifest has an invalid \"{$field}\" entry.");
            }

            $entries[] = $row;
        }

        return $entries;
    }

    /**
     * @param  array<array-key, mixed>  $row
     * @param  list<string>  $fields
     * @return array<string, mixed>
     */
    private function manifestFields(array $row, array $fields): array
    {
        return array_intersect_key($row, array_flip($fields));
    }

    /**
     * @param  array<array-key, mixed>  $row
     * @return list<string>
     */
    private function manifestStrings(array $row, string $field): array
    {
        $strings = [];

        foreach (Arr::array($row, $field) as $value) {
            if (! is_string($value)) {
                throw new RuntimeException("The portfolio backup manifest has an invalid \"{$field}\" value.");
            }

            $strings[] = $value;
        }

        return $strings;
    }

    /**
     * @param  array<int, string>  $entryNames
     * @param  PortfolioManifest  $manifest
     * @return array<string, string>
     */
    private function validateMedia(ZipArchive $zip, array $entryNames, array $manifest): array
    {
        $declaredMedia = [];

        foreach ($manifest['media'] as $metadata) {
            $declaredMedia[$metadata['path']] = $metadata;
        }

        $referencedPaths = array_column($manifest['projects'], 'image');

        foreach (self::SITE_SETTING_MEDIA_FIELDS as $field) {
            $path = $manifest['site_setting'][$field] ?? null;

            if (is_string($path) && $path !== '') {
                $referencedPaths[] = $path;
            }
        }

        if (array_diff($referencedPaths, array_keys($declaredMedia)) !== []) {
            throw new RuntimeException('The portfolio backup is missing referenced media metadata.');
        }

        $expectedEntryNames = array_map(
            fn (string $path): string => 'media/'.$path,
            array_keys($declaredMedia),
        );
        $expectedEntryNames[] = ExportPortfolioBackup::MANIFEST_PATH;
        sort($expectedEntryNames);
        sort($entryNames);

        if ($expectedEntryNames !== $entryNames) {
            throw new RuntimeException('The portfolio backup media does not match its manifest.');
        }

        $mediaContents = [];

        foreach ($declaredMedia as $path => $metadata) {
            $this->assertManagedMediaPath($path);
            $contents = $zip->getFromName('media/'.$path);

            if (! is_string($contents)) {
                throw new RuntimeException("Portfolio media could not be read: {$path}");
            }

            if (strlen($contents) !== $metadata['bytes'] || hash('sha256', $contents) !== $metadata['sha256']) {
                throw new RuntimeException("Portfolio media failed its integrity check: {$path}");
            }

            $this->assertValidMediaContents($path, $contents);
            $mediaContents[$path] = $contents;
        }

        return $mediaContents;
    }

    /**
     * @param  array<string, string>  $mediaContents
     * @param  array<int, string>  $createdPaths
     * @return array<string, string>
     */
    private function storeMedia(array $mediaContents, array &$createdPaths): array
    {
        $disk = Storage::disk('public');
        $mediaPathMap = [];

        foreach ($mediaContents as $originalPath => $contents) {
            if (in_array($originalPath, self::BUILT_IN_PLACEHOLDERS, true)) {
                if (! $disk->exists($originalPath)) {
                    $bundledPlaceholder = database_path('seeders/assets/portfolio/'.$originalPath);

                    if (! is_file($bundledPlaceholder)) {
                        throw new RuntimeException("The bundled portfolio placeholder is missing: {$originalPath}");
                    }

                    $placeholderContents = file_get_contents($bundledPlaceholder);

                    if (! is_string($placeholderContents)) {
                        throw new RuntimeException("The bundled portfolio placeholder could not be read: {$originalPath}");
                    }

                    if (! $disk->put($originalPath, $placeholderContents, 'public')) {
                        throw new RuntimeException("The portfolio placeholder could not be restored: {$originalPath}");
                    }

                    $createdPaths[] = $originalPath;
                }

                $mediaPathMap[$originalPath] = $originalPath;

                continue;
            }

            $extension = Str::lower(pathinfo($originalPath, PATHINFO_EXTENSION));
            $directory = Str::beforeLast($originalPath, '/');
            $restoredPath = $directory.'/'.Str::uuid().'.'.$extension;

            if (! $disk->put($restoredPath, $contents, 'public')) {
                throw new RuntimeException("Portfolio media could not be restored: {$originalPath}");
            }

            $createdPaths[] = $restoredPath;
            $mediaPathMap[$originalPath] = $restoredPath;
        }

        return $mediaPathMap;
    }

    /**
     * @param  PortfolioManifest  $manifest
     * @param  array<string, string>  $mediaPathMap
     */
    private function replacePortfolio(array $manifest, array $mediaPathMap): void
    {
        DB::transaction(function () use ($manifest, $mediaPathMap): void {
            Experience::query()->delete();
            Project::query()->delete();
            Skill::query()->delete();

            $projectIds = [];

            foreach ($manifest['projects'] as $projectData) {
                $attributes = $projectData['attributes'];
                $attributes['image'] = $mediaPathMap[$projectData['image']];
                $projectIds[$projectData['key']] = Project::query()->create($attributes)->id;
            }

            foreach ($manifest['experiences'] as $experienceData) {
                Experience::query()
                    ->create($experienceData['attributes'])
                    ->projects()
                    ->sync(array_map(
                        fn (string $projectKey): int => $projectIds[$projectKey],
                        $experienceData['project_keys'],
                    ));
            }

            foreach ($manifest['skills'] as $skillData) {
                Skill::query()->create($skillData);
            }

            $siteSettingData = $manifest['site_setting'];

            foreach (self::SITE_SETTING_MEDIA_FIELDS as $mediaField) {
                $originalPath = $siteSettingData[$mediaField] ?? null;

                if (is_string($originalPath) && $originalPath !== '') {
                    $siteSettingData[$mediaField] = $mediaPathMap[$originalPath];
                }
            }

            $siteSetting = SiteSetting::current() ?? new SiteSetting;
            $siteSetting->fill($siteSettingData);
            $siteSetting->save();
        });
    }

    private function assertManagedMediaPath(string $path): void
    {
        $hasUnsafeSegments = Str::startsWith($path, ['/'])
            || Str::contains($path, ['\\', "\0"])
            || in_array('..', explode('/', $path), true);
        $extension = Str::lower(pathinfo($path, PATHINFO_EXTENSION));
        $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true);
        $isManaged = (Str::startsWith($path, 'projects/') && $isImage)
            || (Str::startsWith($path, 'site/profile-images/') && $isImage)
            || (Str::startsWith($path, 'site/seo/') && $isImage)
            || (Str::startsWith($path, 'site/resumes/') && $extension === 'pdf')
            || in_array($path, self::BUILT_IN_PLACEHOLDERS, true);

        if ($hasUnsafeSegments || ! $isManaged) {
            throw new RuntimeException("The portfolio backup contains an unsafe media path: {$path}");
        }
    }

    private function assertValidMediaContents(string $path, string $contents): void
    {
        if (in_array($path, self::BUILT_IN_PLACEHOLDERS, true)) {
            if (! Str::contains(Str::lower($contents), '<svg')) {
                throw new RuntimeException("The portfolio placeholder is not a valid SVG: {$path}");
            }

            return;
        }

        $extension = Str::lower(pathinfo($path, PATHINFO_EXTENSION));
        $maximumBytes = match (true) {
            Str::startsWith($path, 'projects/') => 4 * 1024 * 1024,
            Str::startsWith($path, 'site/profile-images/') => 2 * 1024 * 1024,
            Str::startsWith($path, 'site/seo/') => 5 * 1024 * 1024,
            Str::startsWith($path, 'site/resumes/') => 10 * 1024 * 1024,
            default => 0,
        };

        if (strlen($contents) > $maximumBytes) {
            throw new RuntimeException("Portfolio media exceeds its size limit: {$path}");
        }

        if ($extension === 'pdf') {
            if (! Str::startsWith($contents, '%PDF-')) {
                throw new RuntimeException("The portfolio resume is not a valid PDF: {$path}");
            }

            return;
        }

        $imageDetails = getimagesizefromstring($contents);

        if (! is_array($imageDetails)) {
            throw new RuntimeException("Portfolio media is not a valid image: {$path}");
        }

        [$width, $height, $type] = $imageDetails;
        $expectedTypes = [
            'jpg' => IMAGETYPE_JPEG,
            'jpeg' => IMAGETYPE_JPEG,
            'png' => IMAGETYPE_PNG,
            'webp' => IMAGETYPE_WEBP,
        ];

        if (($expectedTypes[$extension] ?? null) !== $type) {
            throw new RuntimeException("Portfolio media does not match its file extension: {$path}");
        }

        if (Str::startsWith($path, 'projects/') && ($width > 2560 || $height > 1600)) {
            throw new RuntimeException("A project image exceeds the supported dimensions: {$path}");
        }

        if (Str::startsWith($path, 'site/profile-images/') && ($width > 2000 || $height > 2000)) {
            throw new RuntimeException("The profile image exceeds the supported dimensions: {$path}");
        }
    }
}
