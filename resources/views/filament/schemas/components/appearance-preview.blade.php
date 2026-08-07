@php
    $rawAppearance = $get('appearance');
    $rawColors = is_array($rawAppearance) && is_array($rawAppearance['colors'] ?? null)
        ? $rawAppearance['colors']
        : [];
    $previewUsesDefaultColors = \App\Models\SiteSetting::appearanceContrastFailures($rawColors) !== [];
    $appearance = \App\Models\SiteSetting::resolveAppearance($rawAppearance);
    $previewStyle = \App\Models\SiteSetting::appearanceStyle($appearance).'; font-family: var(--font-sans)';
    $name = filled($get('name')) ? $get('name') : 'Your name';
    $professionalTitle = filled($get('professional_title')) ? $get('professional_title') : 'Your professional title';
    $heroHeading = filled($get('hero_heading')) ? $get('hero_heading') : 'Hello';
    $heroSubheading = filled($get('hero_subheading')) ? $get('hero_subheading') : 'I build thoughtful digital experiences.';
    $heroDescription = filled($get('hero_description'))
        ? \Illuminate\Support\Str::limit($get('hero_description'), 110)
        : 'A concise introduction to your work, strengths, and the value you create.';
    $portraitOnLeft = $appearance['hero_layout'] === 'portrait_left';
    $alternateProjects = $appearance['project_layout'] === 'alternating';
    $softCorners = $appearance['corner_style'] === 'soft';
@endphp

@once
    @fonts(['poppins', 'inter', 'space-mono', 'playfair-display'])
@endonce

<section aria-labelledby="appearance-preview-title" class="space-y-3 xl:sticky xl:top-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 id="appearance-preview-title" class="text-base font-semibold text-gray-950 dark:text-white">Live portfolio preview</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">A draft of the public hero and project rhythm. Save to publish it.</p>
        </div>

        <div class="flex items-center gap-2 text-xs font-medium">
            @if ($previewUsesDefaultColors)
                <span class="rounded-full bg-warning-50 px-2.5 py-1 text-warning-700 ring-1 ring-inset ring-warning-600/20 dark:bg-warning-400/10 dark:text-warning-400">
                    Using safe colors
                </span>
            @endif
            <span wire:loading.remove class="rounded-full bg-gray-100 px-2.5 py-1 text-gray-600 dark:bg-white/5 dark:text-gray-300">Draft preview</span>
            <span wire:loading.flex class="items-center gap-1.5 rounded-full bg-primary-50 px-2.5 py-1 text-primary-700 dark:bg-primary-400/10 dark:text-primary-400">
                <span class="size-1.5 animate-pulse rounded-full bg-current"></span>
                Updating
            </span>
        </div>
    </div>

    <div class="overflow-hidden border border-gray-200 bg-gray-100 p-2 shadow-sm dark:border-white/10 dark:bg-gray-950 sm:p-4">
        <div
            style="{{ $previewStyle }}"
            @class([
                'mx-auto overflow-hidden border border-[var(--color-ink)] bg-[var(--color-canvas)] text-[var(--color-ink)] shadow-[0.45rem_0.45rem_0_var(--color-brand)] transition-all duration-300',
                'max-w-5xl' => $appearance['page_width'] === 'wide',
                'max-w-3xl' => $appearance['page_width'] === 'standard',
                'rounded-2xl' => $softCorners,
            ])
        >
            <header class="flex items-center justify-between gap-4 border-b border-[var(--color-ink)] px-4 py-3 sm:px-6">
                <div class="flex min-w-0 items-center gap-3">
                    <span @class([
                        'grid size-8 shrink-0 place-items-center bg-[var(--color-brand)] text-xs font-black text-[var(--color-canvas)]',
                        'rounded-lg' => $softCorners,
                    ])>{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($name, 0, 1)) }}</span>
                    <div class="min-w-0">
                        <p class="truncate text-xs font-bold">{{ $name }}</p>
                        <p class="truncate text-[0.625rem] text-[var(--color-ink-muted)]">{{ $professionalTitle }}</p>
                    </div>
                </div>

                <nav aria-label="Preview navigation" class="hidden items-center gap-4 text-[0.625rem] font-bold uppercase tracking-wider sm:flex">
                    <span>About</span>
                    <span>Projects</span>
                    <span>Contact</span>
                </nav>
            </header>

            <div @class([
                'grid items-end gap-6 px-4 py-8 sm:px-8 sm:py-10',
                'sm:grid-cols-[minmax(0,1fr)_7rem]' => ! $portraitOnLeft,
                'sm:grid-cols-[7rem_minmax(0,1fr)]' => $portraitOnLeft,
            ])>
                <div @class([
                    'min-w-0',
                    'sm:col-start-2 sm:row-start-1' => $portraitOnLeft,
                ])>
                    <p class="mb-2 text-[0.625rem] font-bold uppercase tracking-[0.18em] text-[var(--color-brand)]">Selected work / 2026</p>
                    <h3 class="text-[clamp(2.5rem,10vw,5rem)] font-black leading-[0.8] tracking-[-0.075em]">{{ $heroHeading }}<span class="text-[var(--color-brand)]">.</span></h3>
                    <p class="mt-4 max-w-xl text-sm font-bold sm:text-base">{{ $heroSubheading }}</p>
                    <p class="mt-2 max-w-xl text-xs leading-relaxed text-[var(--color-ink-muted)] sm:text-sm">{{ $heroDescription }}</p>
                    <span class="mt-5 inline-flex border-b border-[var(--color-brand)] pb-1 text-[0.625rem] font-bold uppercase tracking-wider text-[var(--color-brand)]">Explore projects</span>
                </div>

                <div @class([
                    'relative h-28 w-24 justify-self-start border border-[var(--color-ink)] bg-[var(--color-panel)] shadow-[0.45rem_0.45rem_0_var(--color-brand)] sm:h-36 sm:w-28',
                    'sm:col-start-1 sm:row-start-1' => $portraitOnLeft,
                    'rounded-xl' => $softCorners,
                ]) aria-label="Profile image position">
                    <span class="absolute inset-x-3 bottom-3 h-16 bg-[var(--color-brand-soft)] opacity-70"></span>
                    <span class="absolute left-1/2 top-5 size-8 -translate-x-1/2 rounded-full border border-[var(--color-ink)] bg-[var(--color-canvas)]"></span>
                </div>
            </div>

            <div class="border-t border-[var(--color-ink)] bg-[var(--color-panel)] px-4 py-6 sm:px-8">
                <div class="mb-5 flex items-center gap-3">
                    <span class="text-[0.625rem] font-black text-[var(--color-brand)]">02 /</span>
                    <h4 class="text-lg font-black tracking-tight">Projects<span class="text-[var(--color-brand)]">.</span></h4>
                    <span class="h-px grow bg-[var(--color-ink)] opacity-30"></span>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach (['Portfolio system', 'Commerce platform'] as $projectTitle)
                        @php
                            $mediaOnRight = $alternateProjects && $loop->even;
                        @endphp
                        <article wire:key="appearance-preview-project-{{ $loop->index }}" @class([
                            'grid items-center gap-3 border-t border-[var(--color-ink)] pt-3',
                            'grid-cols-[4.5rem_minmax(0,1fr)]' => ! $mediaOnRight,
                            'grid-cols-[minmax(0,1fr)_4.5rem]' => $mediaOnRight,
                        ])>
                            <div @class([
                                'aspect-video border border-[var(--color-ink)] bg-[var(--color-brand)] shadow-[0.2rem_0.2rem_0_var(--color-brand-soft)]',
                                'col-start-2 row-start-1' => $mediaOnRight,
                                'rounded-lg' => $softCorners,
                            ])></div>
                            <div @class(['col-start-1 row-start-1' => $mediaOnRight])>
                                <h5 class="text-xs font-black">{{ $projectTitle }}</h5>
                                <p class="mt-1 text-[0.6rem] leading-relaxed text-[var(--color-ink-muted)]">Laravel · Livewire · Product design</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>

            <footer class="flex flex-wrap items-center justify-between gap-2 border-t border-[var(--color-ink)] px-4 py-3 text-[0.625rem] text-[var(--color-ink-muted)] sm:px-8">
                <span>{{ $appearance['page_width'] === 'wide' ? 'Wide canvas' : 'Standard canvas' }}</span>
                <span>{{ \App\Models\SiteSetting::APPEARANCE_OPTIONS['font'][$appearance['font']] }} · {{ $softCorners ? 'Soft corners' : 'Sharp corners' }} · {{ $appearance['motion'] === 'off' ? 'Motion off' : 'Motion on' }} · {{ \Illuminate\Support\Str::headline($appearance['color_scheme']) }} controls</span>
            </footer>
        </div>
    </div>
</section>
