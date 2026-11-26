@php($profile = $profile ?? null)

<form
    method="POST"
    action="{{ $profile ? route('settings.printing.update', $profile) : route('settings.printing.store') }}"
    class="flex h-full flex-col gap-5 p-6"
>
    @csrf
    @if ($profile)
        @method('PUT')
    @endif
    <input type="hidden" name="_form" value="{{ $profile?->id ?? 'create' }}">

    <div class="grid gap-4 sm:grid-cols-2">
        <label class="grid gap-1 text-sm font-semibold">Name (EN)
            <input name="name_en" value="{{ old('name_en', $profile?->name_en) }}" required class="rounded-xl border border-line bg-canvas px-4 py-3 font-normal">
            @error('name_en')<span class="font-normal text-red-700">{{ $message }}</span>@enderror
        </label>
        <label class="grid gap-1 text-sm font-semibold">Name (UR)
            <input name="name_ur" value="{{ old('name_ur', $profile?->name_ur) }}" dir="rtl" class="rounded-xl border border-line bg-canvas px-4 py-3 font-normal">
        </label>

        @unless ($profile)
            <label class="grid gap-1 text-sm font-semibold">Category
                <select name="category" required class="rounded-xl border border-line bg-canvas px-4 py-3 font-normal">
                    <option value="document" @selected(old('category', 'document') === 'document')>Document (A4, A5…)</option>
                    <option value="receipt" @selected(old('category') === 'receipt')>Receipt roll</option>
                    <option value="label" @selected(old('category') === 'label')>Label / barcode</option>
                </select>
            </label>
            <label class="grid gap-1 text-sm font-semibold">Label kind
                <select name="label_kind" class="rounded-xl border border-line bg-canvas px-4 py-3 font-normal">
                    <option value="barcode" @selected(old('label_kind', 'barcode') === 'barcode')>Barcode</option>
                    <option value="product" @selected(old('label_kind') === 'product')>Product</option>
                    <option value="price" @selected(old('label_kind') === 'price')>Price</option>
                </select>
            </label>
        @endunless

        <label class="grid gap-1 text-sm font-semibold">Width (mm)
            <input name="width_mm" type="number" step="0.1" min="10" max="500" value="{{ old('width_mm', $profile?->width_mm ?? 210) }}" required class="rounded-xl border border-line bg-canvas px-4 py-3 font-normal">
            @error('width_mm')<span class="font-normal text-red-700">{{ $message }}</span>@enderror
        </label>
        <label class="grid gap-1 text-sm font-semibold">Height (mm)
            <input name="height_mm" type="number" step="0.1" min="10" max="500" value="{{ old('height_mm', $profile?->height_mm) }}" placeholder="Empty = receipt roll" class="rounded-xl border border-line bg-canvas px-4 py-3 font-normal">
            @error('height_mm')<span class="font-normal text-red-700">{{ $message }}</span>@enderror
        </label>
        <label class="grid gap-1 text-sm font-semibold">Margin (mm)
            <input name="margin_mm" type="number" step="0.1" min="0" max="40" value="{{ old('margin_mm', $profile?->margin_mm ?? 10) }}" required class="rounded-xl border border-line bg-canvas px-4 py-3 font-normal">
        </label>
        <label class="grid gap-1 text-sm font-semibold">Orientation
            <select name="orientation" required class="rounded-xl border border-line bg-canvas px-4 py-3 font-normal">
                <option value="portrait" @selected(old('orientation', $profile?->orientation ?? 'portrait') === 'portrait')>Portrait</option>
                <option value="landscape" @selected(old('orientation', $profile?->orientation) === 'landscape')>Landscape</option>
            </select>
        </label>
        <label class="grid gap-1 text-sm font-semibold sm:col-span-2">Printer hint
            <input name="printer_hint" value="{{ old('printer_hint', $profile?->printer_hint) }}" placeholder="e.g. A4 office printer / Xprinter barcode" class="rounded-xl border border-line bg-canvas px-4 py-3 font-normal">
        </label>
    </div>

    <div class="mt-auto flex justify-end gap-2 border-t border-line pt-4">
        <button type="button" data-window-close class="rounded-xl border border-line bg-canvas px-4 py-2 text-sm font-semibold">Cancel</button>
        <button type="submit" class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold">{{ $profile ? 'Save' : 'Add size' }}</button>
    </div>
</form>
