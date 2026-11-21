@props(['id', 'title', 'tab', 'open' => false])

<div
    id="{{ $id }}"
    data-window
    data-title="{{ $title }}"
    @unless ($open) hidden @endunless
    class="desk-window"
    role="dialog"
    aria-labelledby="{{ $id }}-title"
>
    <div class="desk-window-bar flex shrink-0 items-center justify-between gap-3 border-b border-line bg-soft px-4 py-2" data-window-drag>
        <h2 id="{{ $id }}-title" class="truncate text-sm font-semibold">{{ $title }}</h2>
        <div class="flex shrink-0 items-center gap-1">
            <a href="{{ $tab }}" target="_blank" rel="noopener" class="rounded-lg px-2 py-1 text-xs font-semibold hover:bg-card">New tab</a>
            <button type="button" data-window-min class="rounded-lg px-2 py-1 text-xs font-semibold hover:bg-card">Min</button>
            <button type="button" data-window-max class="rounded-lg px-2 py-1 text-xs font-semibold hover:bg-card">Max</button>
            <button type="button" data-window-close class="rounded-lg px-2 py-1 text-xs font-semibold hover:bg-card">Close</button>
        </div>
    </div>
    <div class="min-h-0 flex-1 overflow-auto">
        {{ $slot }}
    </div>
    <span data-window-resize="e" class="desk-resize-e"></span>
    <span data-window-resize="s" class="desk-resize-s"></span>
    <span data-window-resize="se" class="desk-resize-se"></span>
</div>
