@props(['id', 'title', 'tab' => null, 'open' => false])

<div
    id="{{ $id }}"
    data-window
    data-arrow-pane
    data-title="{{ $title }}"
    @if (! $open) hidden @endif
    class="desk-window"
    role="dialog"
    aria-label="{{ $title }}"
>
    <div class="desk-window-bar flex shrink-0 items-center justify-between gap-3 border-b border-line bg-soft px-3 py-2 sm:px-4" data-window-drag>
        <h2 class="min-w-0 truncate text-sm font-semibold">{{ $title }}</h2>
        <div class="flex shrink-0 items-center gap-0.5">
            @if ($tab)
                <a href="{{ $tab }}" target="_blank" rel="noopener" class="grid h-7 w-7 place-items-center rounded-lg hover:bg-card" aria-label="Open in new tab">
                    <svg viewBox="0 0 16 16" class="h-3.5 w-3.5" aria-hidden="true">
                        <path d="M6.5 3.5H3.5v9h9V9.5" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                        <path d="M9.5 3.5h3v3M12.5 3.5 8 8" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                </a>
            @endif
            <button type="button" data-window-min class="grid h-7 w-7 place-items-center rounded-lg hover:bg-card" aria-label="Minimize">
                <svg viewBox="0 0 16 16" class="h-3.5 w-3.5" aria-hidden="true">
                    <path d="M3.5 8h9" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"></path>
                </svg>
            </button>
            <button type="button" data-window-max class="grid h-7 w-7 place-items-center rounded-lg hover:bg-card" aria-label="Maximize">
                <svg data-window-max-icon viewBox="0 0 16 16" class="h-3.5 w-3.5" aria-hidden="true">
                    <rect x="3.25" y="3.25" width="9.5" height="9.5" rx="1" fill="none" stroke="currentColor" stroke-width="1.5"></rect>
                </svg>
                <svg data-window-restore-icon viewBox="0 0 16 16" class="h-3.5 w-3.5" hidden aria-hidden="true">
                    <path d="M5.5 5.5h6.25v6.25H5.5z" fill="none" stroke="currentColor" stroke-width="1.4"></path>
                    <path d="M4.25 10.5H3.5V3.5h7v.75" fill="none" stroke="currentColor" stroke-width="1.4"></path>
                </svg>
            </button>
            <button type="button" data-window-close class="grid h-7 w-7 place-items-center rounded-lg hover:bg-card" aria-label="Close">
                <svg viewBox="0 0 16 16" class="h-3.5 w-3.5" aria-hidden="true">
                    <path d="M4.5 4.5l7 7M11.5 4.5l-7 7" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"></path>
                </svg>
            </button>
        </div>
    </div>
    <button type="button" class="side-arrow w-full border-b border-line bg-soft" data-scroll-up hidden aria-label="Content above">
        <svg viewBox="0 0 16 16" class="h-4 w-4" aria-hidden="true">
            <path d="M3.5 10.5 8 6l4.5 4.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path>
        </svg>
    </button>
    <div class="arrow-scroll min-h-0 flex-1" data-arrow-scroll>
        {{ $slot }}
    </div>
    <button type="button" class="side-arrow w-full border-t border-line bg-soft" data-scroll-down hidden aria-label="Content below">
        <svg viewBox="0 0 16 16" class="h-4 w-4" aria-hidden="true">
            <path d="M3.5 5.5 8 10l4.5-4.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path>
        </svg>
    </button>
    <span data-window-resize="e" class="desk-resize-e"></span>
    <span data-window-resize="s" class="desk-resize-s"></span>
    <span data-window-resize="se" class="desk-resize-se"></span>
</div>
