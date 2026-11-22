@props(['title'])

<div class="flex shrink-0 items-center gap-0.5">
    <button type="button" data-pane-min class="grid h-6 w-6 place-items-center rounded-lg hover:bg-soft" aria-label="Minimize {{ $title }}">
        <svg viewBox="0 0 16 16" class="h-3.5 w-3.5" aria-hidden="true">
            <path d="M3.5 8h9" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"></path>
        </svg>
    </button>
    <button type="button" data-pane-max class="grid h-6 w-6 place-items-center rounded-lg hover:bg-soft" aria-label="Maximize {{ $title }}">
        <svg data-pane-max-icon viewBox="0 0 16 16" class="h-3.5 w-3.5" aria-hidden="true">
            <rect x="3.25" y="3.25" width="9.5" height="9.5" rx="1" fill="none" stroke="currentColor" stroke-width="1.5"></rect>
        </svg>
        <svg data-pane-restore-icon viewBox="0 0 16 16" class="h-3.5 w-3.5" hidden aria-hidden="true">
            <path d="M5.5 5.5h6.25v6.25H5.5z" fill="none" stroke="currentColor" stroke-width="1.4"></path>
            <path d="M4.25 10.5H3.5V3.5h7v.75" fill="none" stroke="currentColor" stroke-width="1.4"></path>
        </svg>
    </button>
</div>
