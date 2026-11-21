@php
    $note = $note ?? null;
    $formKey = $note ? (string) $note->id : 'create';
    $showOld = old('_form') === $formKey;
@endphp

<form method="POST" action="{{ $note ? route('notes.update', $note) : route('notes.store') }}" class="flex h-full flex-col gap-5 p-6">
    @csrf
    @if ($note)
        @method('PUT')
    @endif
    <input type="hidden" name="_form" value="{{ $formKey }}">
    <label class="flex flex-col gap-2 text-sm font-semibold">
        Title
        <input name="title" type="text" value="{{ $showOld ? old('title') : ($note?->title ?? '') }}" required autocomplete="off" class="rounded-xl border border-line bg-canvas px-4 py-3 text-base font-normal outline-none focus:border-ink">
        @if ($showOld)
            @error('title')
                <span class="text-sm font-normal">{{ $message }}</span>
            @enderror
        @endif
    </label>
    <label class="flex flex-col gap-2 text-sm font-semibold">
        Text
        <textarea name="body" rows="8" required dir="auto" class="rounded-xl border border-line bg-canvas px-4 py-3 text-base font-normal outline-none focus:border-ink">{{ $showOld ? old('body') : ($note?->body ?? '') }}</textarea>
        @if ($showOld)
            @error('body')
                <span class="text-sm font-normal">{{ $message }}</span>
            @enderror
        @endif
    </label>
    <div class="mt-auto flex justify-end gap-2 border-t border-line pt-4">
        @isset($cancel)
            <a href="{{ $cancel }}" class="rounded-xl border border-line bg-canvas px-4 py-2 text-sm font-semibold">Cancel</a>
        @else
            <button type="button" data-window-close class="rounded-xl border border-line bg-canvas px-4 py-2 text-sm font-semibold">Cancel</button>
        @endisset
        <button type="submit" class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold">Save</button>
    </div>
</form>
