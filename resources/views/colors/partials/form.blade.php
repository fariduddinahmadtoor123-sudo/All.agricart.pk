@php
    $scheme = $scheme ?? null;
    $formKey = $scheme ? (string) $scheme->id : 'create';
    $showOld = old('_form') === $formKey;
    $fields = [
        'page' => ['Page', '#f5ebe0'],
        'card' => ['Card', '#edede9'],
        'soft' => ['Soft', '#e3d5ca'],
        'line' => ['Line', '#d6ccc2'],
        'primary' => ['Primary', '#d5bdaf'],
    ];
@endphp

<form method="POST" action="{{ $scheme ? route('colors.update', $scheme) : route('colors.store') }}" class="flex h-full flex-col gap-5 p-6">
    @csrf
    @if ($scheme)
        @method('PUT')
    @endif
    <input type="hidden" name="_form" value="{{ $formKey }}">
    <label class="flex flex-col gap-2 text-sm font-semibold">
        Name
        <input name="name" type="text" value="{{ $showOld ? old('name') : ($scheme?->name ?? '') }}" required autocomplete="off" class="rounded-xl border border-line bg-canvas px-4 py-3 text-base font-normal outline-none focus:border-ink">
        @if ($showOld)
            @error('name')
                <span class="text-sm font-normal">{{ $message }}</span>
            @enderror
        @endif
    </label>
    @foreach ($fields as $field => [$label, $fallback])
        @php($value = $showOld ? old($field, $scheme?->{$field} ?? $fallback) : ($scheme?->{$field} ?? $fallback))
        <label class="flex flex-col gap-2 text-sm font-semibold">
            {{ $label }}
            <span data-color-pair class="flex items-center gap-3">
                <input type="color" value="{{ $value }}" aria-label="{{ $label }} picker" class="h-11 w-14 cursor-pointer rounded-lg border border-line bg-canvas p-1">
                <input name="{{ $field }}" type="text" value="{{ $value }}" required spellcheck="false" class="w-full rounded-xl border border-line bg-canvas px-4 py-3 text-base font-normal outline-none focus:border-ink">
            </span>
            @if ($showOld)
                @error($field)
                    <span class="text-sm font-normal">{{ $message }}</span>
                @enderror
            @endif
        </label>
    @endforeach
    <div class="mt-auto flex justify-end gap-2 border-t border-line pt-4">
        @isset($cancel)
            <a href="{{ $cancel }}" class="rounded-xl border border-line bg-canvas px-4 py-2 text-sm font-semibold">Cancel</a>
        @else
            <button type="button" data-window-close class="rounded-xl border border-line bg-canvas px-4 py-2 text-sm font-semibold">Cancel</button>
        @endisset
        <button type="submit" class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold">Save</button>
    </div>
</form>
