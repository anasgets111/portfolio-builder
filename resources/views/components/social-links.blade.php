@props([
    'links' => [],
    'showLabels' => false,
])

<div {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-3']) }}>
    @foreach ($links as $socialLink)
        @php
            $url = $socialLink['url'] ?? null;
            $label = $socialLink['label'] ?? $socialLink['platform'] ?? 'Social link';
            $platform = \Illuminate\Support\Str::lower($socialLink['platform'] ?? 'link');
            $displayLabel = $showLabels ? ($socialLink['platform'] ?? $label) : $label;
            $isExternal = filled($url) && \Illuminate\Support\Str::startsWith($url, ['http://', 'https://']);
        @endphp

        @if (filled($url))
            <a
                href="{{ $url }}"
                @if ($isExternal) target="_blank" rel="noopener noreferrer" @endif
                data-analytics-event="social_clicked"
                data-analytics-target="{{ $platform }}"
                class="group inline-flex min-h-10 items-center gap-2 rounded-md px-2 text-ink-muted transition hover:text-brand focus-visible:text-brand"
                aria-label="{{ $label }}"
            >
                @switch($platform)
                    @case('linkedin')
                        <x-heroicon-o-briefcase class="size-5" aria-hidden="true" />
                        @break

                    @case('github')
                        <x-heroicon-o-code-bracket class="size-5" aria-hidden="true" />
                        @break

                    @case('phone')
                        <x-heroicon-o-device-phone-mobile class="size-5" aria-hidden="true" />
                        @break

                    @case('whatsapp')
                        <x-heroicon-o-chat-bubble-left-right class="size-5" aria-hidden="true" />
                        @break

                    @case('email')
                        <x-heroicon-o-envelope class="size-5" aria-hidden="true" />
                        @break

                    @default
                        <x-heroicon-o-link class="size-5" aria-hidden="true" />
                @endswitch

                <span @class(['text-sm font-medium', 'sr-only' => ! $showLabels])>{{ $displayLabel }}</span>
            </a>
        @endif
    @endforeach
</div>
