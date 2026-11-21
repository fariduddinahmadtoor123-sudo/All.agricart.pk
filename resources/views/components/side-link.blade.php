@props(['href', 'current' => false])

<a
    href="{{ $href }}"
    {{ $attributes->class([
        'side-label rounded-xl px-4 py-3 text-sm',
        'bg-primary font-semibold' => $current,
        'hover:bg-soft' => ! $current,
    ]) }}
    @if ($current) aria-current="page" @endif
>{{ $slot }}</a>
