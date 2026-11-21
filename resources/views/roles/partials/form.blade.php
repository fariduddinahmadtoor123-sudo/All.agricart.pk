<form method="POST" action="{{ route('roles.store') }}" class="flex h-full flex-col gap-6 p-6">
    @csrf
    <label class="flex flex-col gap-2 text-sm font-semibold">
        Name
        <input name="name" type="text" value="{{ old('name') }}" required autocomplete="off" class="rounded-xl border border-line bg-canvas px-4 py-3 text-base font-normal outline-none focus:border-ink">
        @error('name')
            <span class="text-sm font-normal">{{ $message }}</span>
        @enderror
    </label>
    <label class="flex flex-col gap-2 text-sm font-semibold">
        Description
        <textarea name="description" rows="4" class="rounded-xl border border-line bg-canvas px-4 py-3 text-base font-normal outline-none focus:border-ink">{{ old('description') }}</textarea>
        @error('description')
            <span class="text-sm font-normal">{{ $message }}</span>
        @enderror
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
