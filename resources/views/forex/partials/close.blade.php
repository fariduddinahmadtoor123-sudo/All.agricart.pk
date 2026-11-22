<form method="POST" action="{{ route('forex.positions.close') }}">
    @csrf
    <input type="hidden" name="symbol" value="{{ $quote['symbol'] }}">
    <input type="hidden" name="side" value="{{ $side }}">
    <button
        type="submit"
        class="grid h-6 w-6 place-items-center rounded-lg border border-line bg-canvas text-ink hover:bg-soft disabled:cursor-default disabled:opacity-30"
        aria-label="{{ $label }}"
        title="{{ $label }}"
        @disabled(! $enabled)
    >
        <svg viewBox="0 0 16 16" class="h-3 w-3" aria-hidden="true">
            <path d="M4 4l8 8M12 4l-8 8" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"></path>
        </svg>
    </button>
</form>
