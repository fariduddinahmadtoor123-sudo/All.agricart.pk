@props(['muted' => false, 'red' => false])

@php
    $tone = match (true) {
        $red => 'text-red-700',
        $muted => 'text-ink/30',
        default => 'text-ink',
    };
    $size = $attributes->has('class') ? '' : 'h-6 w-6';
@endphp

<svg viewBox="0 0 48 48" {{ $attributes->merge(['class' => trim($size.' shrink-0 '.$tone)]) }} aria-hidden="true">
    <path fill="none" stroke="currentColor" stroke-width="5.5" stroke-linecap="round" d="M15 20C7 20 3 14 5 6"></path>
    <path fill="none" stroke="currentColor" stroke-width="5.5" stroke-linecap="round" d="M33 20C41 20 45 14 43 6"></path>
    <path fill="currentColor" fill-rule="evenodd" d="M15 17c0-3 3.6-6 9-6s9 3 9 6c2 .4 4 2 4 4.5 0 2-1.2 3.4-2.6 4-.2 2.2-.8 4.2-1.6 5.8.2 1.4.2 2.6.2 3.2 0 5.2-4 8.5-9 8.5s-9-3.3-9-8.5c0-.6 0-1.8.2-3.2-.8-1.6-1.4-3.6-1.6-5.8-1.4-.6-2.6-2-2.6-4 0-2.5 2-4.1 4-4.5zm1.4 11.2a2.7 2.7 0 1 0 5.4 0 2.7 2.7 0 0 0-5.4 0zm9.8 0a2.7 2.7 0 1 0 5.4 0 2.7 2.7 0 0 0-5.4 0z"></path>
</svg>
