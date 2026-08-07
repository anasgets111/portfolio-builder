@props([
    'src',
    'alt',
    'width',
    'height',
    'loading' => 'lazy',
    'fetchpriority' => null,
])

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
