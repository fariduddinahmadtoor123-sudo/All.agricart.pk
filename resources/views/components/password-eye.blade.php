@props([
    'input',
    'label' => 'Show password',
])

<button
    type="button"
    data-password-toggle="{{ $input }}"
    aria-label="{{ $label }}"
    aria-pressed="false"
    {{ $attributes->class('absolute top-1/2 right-1.5 flex size-8 -translate-y-1/2 items-center justify-center rounded-lg text-ink/70 hover:bg-soft hover:text-ink') }}
>
    <svg data-eye-open viewBox="0 0 16 16" class="size-4" aria-hidden="true">
        <path d="M1.5 8s2.5-4.5 6.5-4.5S14.5 8 14.5 8s-2.5 4.5-6.5 4.5S1.5 8 1.5 8Z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"></path>
        <circle cx="8" cy="8" r="2" fill="none" stroke="currentColor" stroke-width="1.4"></circle>
    </svg>
    <svg data-eye-shut viewBox="0 0 16 16" class="size-4" hidden aria-hidden="true">
        <path d="M2 2.5 14 13.5" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"></path>
        <path d="M6.2 4.1A7.2 7.2 0 0 1 8 3.5c4 0 6.5 4.5 6.5 4.5a11 11 0 0 1-2.1 2.3M4.2 5.7A11 11 0 0 0 1.5 8S4 12.5 8 12.5c.7 0 1.4-.1 2-.3" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"></path>
    </svg>
</button>
