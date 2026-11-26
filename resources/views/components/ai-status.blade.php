@props(['status', 'linked' => false])

@php
    $label = $status['label'] ?? 'AI is off. It is not connected.';
    $state = $status['state'] ?? 'off';
@endphp

@if ($linked)
    <a href="{{ route('settings.ai') }}" class="ai-status" title="{{ $label }}" aria-label="{{ $label }}">
@else
    <span class="ai-status" title="{{ $label }}" aria-label="{{ $label }}">
@endif
    <svg viewBox="0 0 16 16" class="h-4 w-4" aria-hidden="true">
        <path d="M8 1.5 9.1 6 13.5 7.1 9.1 8.2 8 12.5 6.9 8.2 2.5 7.1 6.9 6 8 1.5Z" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round"></path>
    </svg>
    <span class="ai-signal ai-signal-{{ $state }}" aria-hidden="true"></span>
@if ($linked)
    </a>
@else
    </span>
@endif
