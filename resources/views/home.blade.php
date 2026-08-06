@php
    $logoInitials = \Illuminate\Support\Str::of($siteSetting?->name ?: 'Portfolio')
        ->explode(' ')
        ->filter()
        ->take(2)
        ->map(fn (string $namePart): string => \Illuminate\Support\Str::substr($namePart, 0, 1))
        ->implode('');

    $seededImages = [
        'site/profile-images/profile-placeholder.svg' => [
            'width' => 960,
            'height' => 960,
            'webpSources' => [],
        ],
        'projects/project-placeholder.svg' => [
            'width' => 1600,
            'height' => 900,
            'webpSources' => [],
        ],
    ];

    $profileImage = is_string($siteSetting?->profile_image)
        ? ($seededImages[$siteSetting->profile_image] ?? null)
        : null;

    $hasScrollingSkills = $skills->count() > 14;
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="bg-canvas">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $siteSetting?->seo_title ?: $siteSetting?->name ?: config('app.name') }}</title>

        @if (filled($siteSetting?->seo_description))
            <meta name="description" content="{{ $siteSetting->seo_description }}">
        @endif

        @if (filled($siteSetting?->seo_keywords))
            <meta name="keywords" content="{{ implode(', ', $siteSetting->seo_keywords) }}">
        @endif

        @if (filled($siteSetting?->site_url))
            <link rel="canonical" href="{{ $siteSetting->site_url }}">
        @endif

        @if (filled($siteSetting?->og_image))
            <meta property="og:title" content="{{ $siteSetting->seo_title ?: $siteSetting->name }}">
            <meta property="og:description" content="{{ $siteSetting->seo_description }}">
            <meta property="og:type" content="website">
            @if (filled($siteSetting->site_url))
                <meta property="og:url" content="{{ $siteSetting->site_url }}">
            @endif
            <meta property="og:image" content="{{ asset('storage/'.$siteSetting->og_image) }}">
            <meta property="og:image:alt" content="{{ $siteSetting->name }} portfolio preview">
        @endif

        <meta name="twitter:card" content="{{ filled($siteSetting?->og_image) ? 'summary_large_image' : 'summary' }}">
        <meta name="twitter:title" content="{{ $siteSetting?->seo_title ?: $siteSetting?->name ?: config('app.name') }}">
        @if (filled($siteSetting?->seo_description))
            <meta name="twitter:description" content="{{ $siteSetting->seo_description }}">
        @endif
        @if (filled($siteSetting?->twitter_handle))
            <meta name="twitter:creator" content="{{ '@'.ltrim($siteSetting->twitter_handle, '@') }}">
        @endif
        @if (filled($siteSetting?->og_image))
            <meta name="twitter:image" content="{{ asset('storage/'.$siteSetting->og_image) }}">
        @endif

        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-canvas font-sans text-ink antialiased">
        <a href="#main" class="fixed left-4 top-4 z-[100] -translate-y-24 rounded-md bg-brand px-4 py-3 font-bold text-canvas transition focus:translate-y-0">
            Skip to content
        </a>

        <div id="top" class="min-h-screen">
            <aside class="sticky top-0 z-40 border-b border-ink bg-canvas">
                <nav class="mx-auto flex min-h-18 w-full max-w-7xl items-center gap-1 overflow-x-auto px-3 [scrollbar-width:none] md:px-4 [&::-webkit-scrollbar]:hidden" aria-label="Portfolio sections">
                    <a href="#top" class="mr-auto flex size-11 shrink-0 items-center justify-center bg-brand text-[1.375rem] font-black leading-none text-canvas transition-colors hover:bg-brand-soft focus-visible:bg-brand-soft" aria-label="Back to the beginning">
                        {{ $logoInitials }}<span class="text-ink">.</span>
                    </a>

                    @foreach ([['about', 'About'], ['projects', 'Projects'], ['experience', 'Exp.'], ['contact', 'Contact']] as [$sectionId, $label])
                        <a
                            href="#{{ $sectionId }}"
                            data-nav-link
                            class="flex h-18 shrink-0 items-center justify-center border-b-2 border-transparent px-2.5 text-[0.8rem] font-bold uppercase tracking-[0.08em] text-ink-muted transition hover:border-brand hover:text-ink md:px-3 md:text-xs aria-[current=location]:border-brand aria-[current=location]:text-brand"
                        >
                            {{ $label }}
                        </a>
                    @endforeach

                    @if (filled($siteSetting?->resume_file))
                        <a href="{{ route('cv.show') }}" target="_blank" rel="noopener noreferrer" class="flex size-10 shrink-0 items-center justify-center gap-2 bg-brand text-xs font-bold uppercase tracking-[0.08em] text-canvas transition-colors hover:bg-brand-soft focus-visible:bg-brand-soft sm:w-auto sm:px-3" aria-label="Open my CV">
                            <x-heroicon-o-document-arrow-down class="size-4" aria-hidden="true" />
                            <span class="sr-only sm:not-sr-only">CV</span>
                        </a>
                    @endif
                </nav>
            </aside>

            <main id="main" tabindex="-1" class="scroll-mt-18 outline-none">
                <section class="mx-auto mb-32 mt-12 max-w-7xl px-4 py-8 md:mb-56 md:mt-24" aria-labelledby="hero-title">
                    <div class="flex flex-col-reverse items-start gap-12 md:grid md:grid-cols-[minmax(0,1fr)_13rem] md:items-end md:gap-[clamp(3rem,8vw,8rem)]">
                        <div class="max-w-[62rem]">
                            <div data-reveal>
                                <h1 id="hero-title" class="text-[clamp(4rem,20vw,6.5rem)] font-black leading-[0.78] tracking-[-0.09em] md:text-[clamp(5rem,12vw,10rem)]">
                                    {{ $siteSetting?->hero_heading ?: 'Portfolio' }}
                                </h1>
                            </div>

                            @if (filled($siteSetting?->hero_subheading))
                                <div data-reveal>
                                    <p class="mt-4 max-w-xl text-[clamp(1.5rem,3vw,2rem)] font-bold leading-tight text-brand">
                                        {{ $siteSetting->hero_subheading }}
                                    </p>
                                </div>
                            @endif

                            @if (filled($siteSetting?->hero_description))
                                <div data-reveal>
                                    <p class="mt-6 max-w-2xl text-lg font-extralight leading-relaxed text-ink-muted">
                                        {{ $siteSetting->hero_description }}
                                    </p>
                                </div>
                            @endif

                            <div class="mt-7" data-reveal>
                                <a href="#contact" class="inline-flex min-h-12 items-center justify-center gap-6 bg-brand px-6 text-base font-bold text-canvas transition-colors hover:bg-brand-soft focus-visible:bg-brand-soft">
                                    Contact me <span aria-hidden="true">↘</span>
                                </a>
                            </div>
                        </div>

                        @if (filled($siteSetting?->profile_image))
                            <div class="group shrink-0" data-reveal>
                                <x-responsive-image
                                    :src="$siteSetting->profile_image"
                                    :webp-sources="$profileImage['webpSources'] ?? []"
                                    sizes="(min-width: 768px) 208px, 144px"
                                    :alt="$siteSetting->name.' — '.$siteSetting->professional_title"
                                    :width="$profileImage['width'] ?? 1"
                                    :height="$profileImage['height'] ?? 1"
                                    loading="eager"
                                    fetchpriority="high"
                                    class="h-44 w-36 object-cover grayscale outline-1 outline-ink shadow-[0.75rem_0.75rem_0_var(--color-brand)] transition duration-300 group-hover:-translate-x-1 group-hover:-translate-y-1 group-hover:shadow-[1rem_1rem_0_var(--color-brand)] md:h-68 md:w-52"
                                />
                            </div>
                        @endif
                    </div>
                </section>

                <section id="about" data-observed-section class="scroll-mt-18 border-t border-ink px-6 py-20 md:px-12 md:py-32 lg:px-24" aria-labelledby="about-title">
                    <div class="mx-auto max-w-[74rem]">
                        <div class="mb-12 grid grid-cols-[2.5rem_minmax(0,1fr)] items-center gap-3 md:mb-20 md:grid-cols-[3rem_auto_minmax(2rem,1fr)] md:gap-6">
                            <span class="text-xs font-extrabold tracking-[0.12em] text-brand" aria-hidden="true">01 /</span>
                            <h2 id="about-title" class="shrink-0 text-[clamp(3rem,7vw,5.5rem)] font-black tracking-[-0.07em]">About<span class="text-brand">.</span></h2>
                            <div class="hidden h-px w-full bg-ink md:block" aria-hidden="true"></div>
                        </div>

                        <div class="grid gap-16 md:grid-cols-[minmax(0,1fr)_18rem] md:gap-28">
                            <div class="space-y-6 text-lg font-extralight leading-relaxed text-ink-muted [&>p:first-child]:text-[clamp(1.25rem,2.5vw,1.6rem)] [&>p:first-child]:font-medium [&>p:first-child]:leading-[1.55] [&>p:first-child]:text-ink [&_a]:text-brand [&_a]:underline-offset-4 hover:[&_a]:underline" data-reveal>
                                {!! str($siteSetting?->about_content ?? '')->sanitizeHtml() !!}
                            </div>

                            <aside id="skills" class="border-t border-ink pt-6" aria-labelledby="skills-title" data-reveal>
                                <h3 id="skills-title" class="mb-6 flex items-center gap-2 text-[1.375rem] font-bold">
                                    <x-heroicon-o-code-bracket class="size-6 text-brand" aria-hidden="true" />
                                    Skills
                                </h3>

                            <div
                                data-skills-window
                                @if ($hasScrollingSkills) data-animated tabindex="0" aria-label="Skills list; focus or hover to pause animation" @endif
                                @class(['overflow-hidden', 'h-50' => $hasScrollingSkills])
                            >
                                <div data-skills-track>
                                    <ul data-skills-list class="grid grid-cols-2">
                                        @foreach ($skills as $skill)
                                            <li class="border-b border-ink/20 py-3 text-xs font-bold uppercase tracking-[0.06em] odd:pr-3 even:pl-3">{{ $skill->name }}</li>
                                        @endforeach
                                    </ul>

                                    @if ($hasScrollingSkills)
                                        <ul data-skills-clone aria-hidden="true" class="grid grid-cols-2">
                                            @foreach ($skills as $skill)
                                                <li class="border-b border-ink/20 py-3 text-xs font-bold uppercase tracking-[0.06em] odd:pr-3 even:pl-3">{{ $skill->name }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            </div>

                                <div class="mt-6 flex items-center gap-2 text-sm text-brand">
                                    <span>My links</span>
                                    <x-heroicon-o-arrow-right class="size-4" aria-hidden="true" />
                                </div>
                                <x-social-links :links="$siteSetting?->social_links ?? []" class="mt-1" />
                            </aside>
                        </div>
                    </div>
                </section>

                <section id="projects" data-observed-section class="scroll-mt-18 border-t border-ink px-6 py-20 md:px-12 md:py-32 lg:px-24" aria-labelledby="projects-title">
                    <div class="mx-auto max-w-7xl">
                        <div class="mb-12 grid grid-cols-[2.5rem_minmax(0,1fr)] items-center gap-3 md:mb-20 md:grid-cols-[3rem_auto_minmax(2rem,1fr)] md:gap-6">
                            <span class="text-xs font-extrabold tracking-[0.12em] text-brand" aria-hidden="true">02 /</span>
                            <h2 id="projects-title" class="shrink-0 text-[clamp(3rem,7vw,5.5rem)] font-black tracking-[-0.07em]">Projects<span class="text-brand">.</span></h2>
                            <div class="hidden h-px w-full bg-ink md:block" aria-hidden="true"></div>
                        </div>

                        <div class="grid gap-24 md:gap-36">
                            @foreach ($projects as $project)
                                @php
                                    $projectImage = $seededImages[$project->image] ?? null;
                                @endphp

                                <article
                                    id="project-{{ $project->id }}"
                                    @class([
                                        'relative grid min-w-0 scroll-mt-18 gap-6 border-t border-ink pt-12 md:items-center md:gap-[clamp(3rem,7vw,6rem)]',
                                        'md:grid-cols-[minmax(20rem,1.35fr)_minmax(16rem,0.65fr)]' => $loop->odd,
                                        'md:grid-cols-[minmax(16rem,0.65fr)_minmax(20rem,1.35fr)]' => $loop->even,
                                    ])
                                    data-reveal
                                >
                                    <span class="absolute left-0 top-3 text-xs font-extrabold tracking-[0.12em] text-brand" aria-hidden="true">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                    <button
                                        type="button"
                                        data-dialog-open="project-dialog-{{ $project->id }}"
                                        aria-haspopup="dialog"
                                        aria-controls="project-dialog-{{ $project->id }}"
                                        @class([
                                            'group relative block aspect-video w-full overflow-hidden border border-ink bg-panel text-left shadow-[0.5rem_0.5rem_0_var(--color-brand)] focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-4 focus-visible:ring-offset-canvas md:shadow-[0.75rem_0.75rem_0_var(--color-brand)]',
                                            'md:col-start-2' => $loop->even,
                                        ])
                                    >
                                        <x-responsive-image
                                            :src="$project->image"
                                            :webp-sources="$projectImage['webpSources'] ?? []"
                                            sizes="(min-width: 1280px) 800px, (min-width: 768px) 62vw, calc(100vw - 3rem)"
                                            alt="Screenshot of the {{ $project->title }} project"
                                            :width="$projectImage['width'] ?? 16"
                                            :height="$projectImage['height'] ?? 9"
                                            loading="lazy"
                                            picture-class="block size-full"
                                            class="size-full object-cover object-top transition duration-300 group-hover:scale-105"
                                        />
                                        <span class="absolute inset-x-0 bottom-0 bg-linear-to-t from-black/80 to-transparent px-4 pb-3 pt-12 text-sm font-medium opacity-0 transition group-hover:opacity-100 group-focus-visible:opacity-100">View project details</span>
                                    </button>

                                    <div @class(['md:col-start-1 md:row-start-1' => $loop->even])>
                                        <div class="flex min-w-0 items-center gap-3">
                                            <h3 class="min-w-0 text-[clamp(1.75rem,3vw,2.5rem)] font-black tracking-[-0.05em]">{{ $project->title }}</h3>
                                            <div class="h-px min-w-4 grow bg-ink/30" aria-hidden="true"></div>

                                            @if (filled($project->source_url))
                                                <a href="{{ $project->source_url }}" target="_blank" rel="noopener noreferrer" class="shrink-0 text-ink-muted transition hover:text-brand" aria-label="View {{ $project->title }} source code">
                                                    <x-heroicon-o-code-bracket class="size-6" aria-hidden="true" />
                                                </a>
                                            @endif

                                            @if (filled($project->live_url))
                                                <a href="{{ $project->live_url }}" target="_blank" rel="noopener noreferrer" class="shrink-0 text-ink-muted transition hover:text-brand" aria-label="View the live {{ $project->title }} project">
                                                    <x-heroicon-o-arrow-top-right-on-square class="size-6" aria-hidden="true" />
                                                </a>
                                            @endif
                                        </div>

                                        <div class="mt-2 flex flex-wrap gap-x-2 gap-y-1 text-[0.7rem] font-bold uppercase tracking-[0.06em] text-brand">
                                            @foreach ($project->technologies as $technology)
                                                <span>{{ $technology }}@unless($loop->last)<span aria-hidden="true"> ·</span>@endunless</span>
                                            @endforeach
                                        </div>

                                        <p class="mt-3 text-[1.05rem] font-extralight leading-7 text-ink-muted">{{ $project->summary }}</p>
                                        <button type="button" data-dialog-open="project-dialog-{{ $project->id }}" aria-haspopup="dialog" aria-controls="project-dialog-{{ $project->id }}" class="mt-5 border-b border-current pb-1 text-sm font-bold text-brand">
                                            Learn more <span aria-hidden="true">›</span>
                                        </button>
                                    </div>

                                    <dialog id="project-dialog-{{ $project->id }}" aria-labelledby="project-dialog-title-{{ $project->id }}" aria-describedby="project-dialog-description-{{ $project->id }}" class="project-dialog m-auto w-[min(64rem,calc(100%-2rem))] max-h-[calc(100dvh-2rem)] overflow-y-auto rounded-xl bg-panel p-0 text-ink shadow-2xl">
                                        <div class="relative">
                                            <button type="button" data-dialog-close class="absolute right-3 top-3 z-10 flex size-11 items-center justify-center rounded-full bg-canvas/80 text-ink backdrop-blur transition hover:bg-brand hover:text-canvas" aria-label="Close {{ $project->title }} details">
                                                <x-heroicon-o-x-mark class="size-6" aria-hidden="true" />
                                            </button>

                                            <x-responsive-image
                                                :src="$project->image"
                                                :webp-sources="$projectImage['webpSources'] ?? []"
                                                sizes="(min-width: 1056px) 1024px, calc(100vw - 2rem)"
                                                alt="Detailed screenshot of the {{ $project->title }} project"
                                                :width="$projectImage['width'] ?? 16"
                                                :height="$projectImage['height'] ?? 9"
                                                loading="lazy"
                                                picture-class="block w-full"
                                                class="aspect-video w-full object-cover object-top"
                                            />

                                            <div class="mx-auto max-w-[52rem] p-6 sm:p-8 lg:p-10">
                                                <h2 id="project-dialog-title-{{ $project->id }}" class="text-3xl font-bold">{{ $project->title }}</h2>
                                                <div class="mt-2 flex flex-wrap gap-2 text-sm text-brand">
                                                    @foreach ($project->technologies as $technology)
                                                        <span>{{ $technology }}@unless($loop->last)<span aria-hidden="true"> ·</span>@endunless</span>
                                                    @endforeach
                                                </div>

                                                <div class="mt-6 space-y-4 text-base font-extralight leading-relaxed text-ink-muted [&_a]:text-brand [&_a]:underline" id="project-dialog-description-{{ $project->id }}">
                                                    {!! str($project->body)->sanitizeHtml() !!}
                                                </div>

                                                @if ($project->publishedExperiences->isNotEmpty())
                                                    <div class="mt-7 border-t border-ink/10 pt-6">
                                                        <h3 class="text-xl font-bold">Related Experience<span class="text-brand">.</span></h3>
                                                        <div class="mt-3 flex flex-col items-start gap-2 text-sm">
                                                            @foreach ($project->publishedExperiences as $experience)
                                                                <a
                                                                    href="#experience-{{ $experience->id }}"
                                                                    data-dialog-navigate
                                                                    data-related-experience
                                                                    class="rounded text-brand underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2 focus-visible:ring-offset-panel"
                                                                >
                                                                    {{ $experience->company }} — {{ $experience->position }}
                                                                </a>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif

                                                @if (filled($project->source_url) || filled($project->live_url))
                                                    <div class="mt-7 border-t border-ink/10 pt-6">
                                                        <h3 class="text-xl font-bold">Project Links<span class="text-brand">.</span></h3>
                                                        <div class="mt-3 flex flex-wrap gap-4 text-sm text-brand">
                                                            @if (filled($project->source_url))
                                                                <a href="{{ $project->source_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded underline-offset-4 hover:underline">
                                                                    <x-heroicon-o-code-bracket class="size-5" aria-hidden="true" />
                                                                    Source code
                                                                </a>
                                                            @endif
                                                            @if (filled($project->live_url))
                                                                <a href="{{ $project->live_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded underline-offset-4 hover:underline">
                                                                    <x-heroicon-o-arrow-top-right-on-square class="size-5" aria-hidden="true" />
                                                                    Live project
                                                                </a>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </dialog>
                                </article>
                            @endforeach
                        </div>
                    </div>
                </section>

                <section id="experience" data-observed-section class="scroll-mt-18 border-t border-ink px-6 py-20 md:px-12 md:py-32 lg:px-24" aria-labelledby="experience-title">
                    <div class="mx-auto max-w-[74rem]">
                        <div class="mb-12 grid grid-cols-[2.5rem_minmax(0,1fr)] items-center gap-3 md:mb-20 md:grid-cols-[3rem_auto_minmax(2rem,1fr)] md:gap-6">
                            <span class="text-xs font-extrabold tracking-[0.12em] text-brand" aria-hidden="true">03 /</span>
                            <h2 id="experience-title" class="shrink-0 text-[clamp(3rem,7vw,5.5rem)] font-black tracking-[-0.07em]">Experience<span class="text-brand">.</span></h2>
                            <div class="hidden h-px w-full bg-ink md:block" aria-hidden="true"></div>
                        </div>

                        <div class="border-t border-ink">
                            @foreach ($experiences as $experience)
                                <article id="experience-{{ $experience->id }}" class="grid scroll-mt-18 border-b border-ink py-12 md:grid-cols-[13rem_minmax(0,1fr)] md:gap-x-[clamp(2rem,7vw,6rem)]" data-reveal>
                                    <h3 class="text-[clamp(1.5rem,3vw,2rem)] font-black tracking-[-0.04em] md:col-start-2 md:row-start-1">{{ $experience->company }}</h3>
                                    <p class="mt-3 text-[0.7rem] font-bold uppercase tracking-[0.06em] text-ink-muted md:col-start-1 md:row-start-1 md:mt-0">
                                        <time datetime="{{ $experience->start_date->toDateString() }}">{{ $experience->start_date->format('F Y') }}</time>
                                        <span aria-hidden="true"> — </span>
                                        @if ($experience->end_date)
                                            <time datetime="{{ $experience->end_date->toDateString() }}">{{ $experience->end_date->format('F Y') }}</time>
                                        @else
                                            Present
                                        @endif
                                    </p>

                                    <p class="mt-5 font-bold text-brand md:col-start-2 md:row-start-2 md:mt-1.5">{{ $experience->position }}</p>
                                    <p class="mt-1 text-[0.7rem] font-bold uppercase tracking-[0.06em] text-ink-muted md:col-start-1 md:row-start-2 md:mt-1.5">{{ $experience->location }}</p>

                                    <p class="mt-4 text-base font-extralight leading-relaxed text-ink-muted md:col-start-2">{{ $experience->description }}</p>
                                    <div class="mt-5 flex flex-wrap gap-3 md:col-start-2">
                                        @foreach ($experience->technologies as $technology)
                                            <span class="border-b border-ink/25 py-1 text-xs font-bold uppercase tracking-[0.04em]">{{ $technology }}</span>
                                        @endforeach
                                    </div>

                                    @if ($experience->publishedProjects->isNotEmpty())
                                        <div class="mt-6 md:col-start-2" aria-labelledby="experience-projects-{{ $experience->id }}">
                                            <h4 id="experience-projects-{{ $experience->id }}" class="text-sm font-bold uppercase tracking-wider text-ink-muted">Related Projects</h4>
                                            <div class="mt-3 flex flex-wrap gap-3">
                                                @foreach ($experience->publishedProjects as $project)
                                                    <a
                                                        href="#project-{{ $project->id }}"
                                                        data-dialog-open="project-dialog-{{ $project->id }}"
                                                        data-related-project
                                                        aria-haspopup="dialog"
                                                        aria-controls="project-dialog-{{ $project->id }}"
                                                        class="inline-flex items-center border-b border-current py-1 text-sm font-medium text-brand transition hover:text-brand-soft focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2 focus-visible:ring-offset-canvas"
                                                    >
                                                        {{ $project->title }}
                                                    </a>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    </div>
                </section>

                <section id="contact" data-observed-section class="scroll-mt-18 border-t border-ink bg-brand px-6 py-20 text-canvas md:px-12 md:py-32 lg:px-24" aria-labelledby="contact-title">
                    <div class="mx-auto max-w-[74rem]" data-reveal>
                        <p class="mb-8 text-xs font-extrabold uppercase tracking-[0.12em]" aria-hidden="true">04 / Contact</p>
                        <h2 id="contact-title" class="text-[clamp(4rem,20vw,7rem)] font-black leading-[0.78] tracking-[-0.09em] md:text-[clamp(5rem,13vw,11rem)]">Contact.</h2>

                        <div class="mt-7 max-w-3xl text-lg font-extralight leading-relaxed text-canvas/80 [&_a]:text-canvas [&_a]:underline-offset-4 hover:[&_a]:underline">
                            {!! str($siteSetting?->contact_content ?? '')->sanitizeHtml() !!}
                        </div>

                        @if (filled($siteSetting?->email))
                            <a href="mailto:{{ $siteSetting->email }}" class="mt-7 inline-flex min-h-14 items-center justify-center gap-2 bg-ink px-8 text-[1.375rem] font-medium text-canvas transition hover:bg-canvas hover:text-ink">
                                <x-heroicon-o-envelope class="size-6" aria-hidden="true" />
                                Send Email
                            </a>
                        @endif

                        <x-social-links :links="$siteSetting?->social_links ?? []" :show-labels="true" class="mt-6 [&>a]:text-canvas [&>a:hover]:text-ink" />
                    </div>
                </section>
            </main>

            <footer class="flex min-h-48 flex-col items-center justify-end gap-3 bg-ink px-6 pb-8 text-center text-sm text-canvas">
                @if (filled($siteSetting?->name))
                    <p>&copy; {{ now()->year }} {{ $siteSetting->name }}</p>
                @endif
                <a href="#top" class="rounded text-brand underline-offset-4 hover:underline">Back to top</a>
            </footer>
        </div>
    </body>
</html>
