@php
    $title = filled($get('seo_title'))
        ? $get('seo_title')
        : (filled($get('name')) ? $get('name') : config('app.name'));
    $description = filled($get('seo_description'))
        ? $get('seo_description')
        : (filled($get('hero_description')) ? $get('hero_description') : 'Add a concise summary of the portfolio.');
    $description = \Illuminate\Support\Str::limit(\Illuminate\Support\Str::squish(strip_tags($description)), 180);
    $productionUrl = filled($get('site_url')) ? rtrim($get('site_url'), '/') : 'https://portfolio.example.com';
    $displayUrl = \Illuminate\Support\Str::limit($productionUrl, 64);
    $isIndexable = (bool) $get('is_indexable');
    $configuredHost = parse_url($productionUrl, PHP_URL_HOST);
    $hostDiffers = is_string($configuredHost) && $configuredHost !== request()->getHost();
    $imageState = $get('og_image');
    $imageUrl = null;

    if (is_string($imageState) && $imageState !== '') {
        $storageUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($imageState);
        $imageUrl = \Illuminate\Support\Str::startsWith($storageUrl, ['http://', 'https://'])
            ? $storageUrl
            : url($storageUrl);
    }
@endphp

<section aria-labelledby="seo-preview-title" class="space-y-4 xl:sticky xl:top-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 id="seo-preview-title" class="text-base font-semibold text-gray-950 dark:text-white">Metadata preview</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Approximate search and social presentation. Platforms may adjust the final result.</p>
        </div>

        <span @class([
            'rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset',
            'bg-success-50 text-success-700 ring-success-600/20 dark:bg-success-400/10 dark:text-success-400' => $isIndexable && filled($get('site_url')),
            'bg-warning-50 text-warning-700 ring-warning-600/20 dark:bg-warning-400/10 dark:text-warning-400' => ! ($isIndexable && filled($get('site_url'))),
        ])>
            {{ $isIndexable && filled($get('site_url')) ? 'Indexing enabled' : 'Protected from indexing' }}
        </span>
    </div>

    @if ($isIndexable && blank($get('site_url')))
        <div class="rounded-xl bg-danger-50 p-3 text-sm text-danger-700 ring-1 ring-inset ring-danger-600/20 dark:bg-danger-400/10 dark:text-danger-400">
            Add the production URL before enabling indexing.
        </div>
    @elseif ($hostDiffers && filled($get('site_url')))
        <div class="rounded-xl bg-info-50 p-3 text-sm text-info-700 ring-1 ring-inset ring-info-600/20 dark:bg-info-400/10 dark:text-info-400">
            The production host differs from this admin host. This is expected when editing locally or on staging.
        </div>
    @endif

    <div class="space-y-2 rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
        <p class="text-sm text-success-700 dark:text-success-400">{{ $displayUrl }}</p>
        <h3 class="text-xl font-medium leading-snug text-primary-700 dark:text-primary-400">{{ $title }}</h3>
        <p class="text-sm leading-relaxed text-gray-600 dark:text-gray-300">{{ $description }}</p>
        <div class="flex flex-wrap gap-2 pt-1 text-xs text-gray-500 dark:text-gray-400">
            <span>{{ \Illuminate\Support\Str::length($title) }} title characters</span>
            <span aria-hidden="true">·</span>
            <span>{{ \Illuminate\Support\Str::length($description) }} description characters</span>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
        <div class="aspect-[1.91/1] bg-gray-100 dark:bg-gray-800">
            @if ($imageUrl)
                <img src="{{ $imageUrl }}" alt="Draft social preview" class="size-full object-cover">
            @else
                <div class="grid size-full place-items-center px-6 text-center text-sm text-gray-500 dark:text-gray-400">
                    Upload a 1.91:1 social image to complete the preview.
                </div>
            @endif
        </div>
        <div class="space-y-1 border-t border-gray-200 p-4 dark:border-white/10">
            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $configuredHost ?: 'portfolio.example.com' }}</p>
            <h3 class="font-semibold text-gray-950 dark:text-white">{{ $title }}</h3>
            <p class="line-clamp-2 text-sm text-gray-600 dark:text-gray-300">{{ $description }}</p>
        </div>
    </div>
</section>
