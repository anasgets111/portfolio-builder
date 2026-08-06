@props([
    'src',
    'alt',
    'width',
    'height',
    'webpSources' => [],
    'sizes' => null,
    'loading' => 'lazy',
    'fetchpriority' => null,
    'pictureClass' => null,
])

@php
    $webpSrcset = collect($webpSources)
        ->map(fn (array $source): string => asset('storage/'.$source['src']).' '.$source['width'].'w')
        ->implode(', ');
@endphp

<picture @class([$pictureClass])>
    @if ($webpSrcset !== '')
        <source
            type="image/webp"
            srcset="{{ $webpSrcset }}"
            @if (filled($sizes)) sizes="{{ $sizes }}" @endif
        >
    @endif

    <img
        src="{{ asset('storage/'.$src) }}"
        alt="{{ $alt }}"
        width="{{ $width }}"
        height="{{ $height }}"
        loading="{{ $loading }}"
        decoding="async"
        @if (filled($fetchpriority)) fetchpriority="{{ $fetchpriority }}" @endif
        {{ $attributes }}
    >
</picture>
