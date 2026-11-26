<div class="flex h-full flex-col gap-5 p-6">
    @include('contacts.partials.ui-preview-note')

    <div class="grid gap-4 sm:grid-cols-2">
        <label class="grid gap-1 text-sm font-semibold">Business name (EN)
            <input type="text" disabled class="rounded-xl border border-line bg-canvas px-4 py-3 font-normal opacity-80">
        </label>
        <label class="grid gap-1 text-sm font-semibold">Business name (UR)
            <input type="text" disabled dir="rtl" class="rounded-xl border border-line bg-canvas px-4 py-3 font-normal opacity-80">
        </label>
        <label class="grid gap-1 text-sm font-semibold">Carrier type
            <select disabled class="rounded-xl border border-line bg-canvas px-4 py-3 font-normal opacity-80">
                <option value="">Choose type</option>
                @foreach ($transportTypes as $key => $labels)
                    <option value="{{ $key }}">{{ $labels['en'] ?? $key }}</option>
                @endforeach
            </select>
        </label>
        <label class="grid gap-1 text-sm font-semibold">Status
            <select disabled class="rounded-xl border border-line bg-canvas px-4 py-3 font-normal opacity-80">
                <option>Active</option>
                <option>Inactive</option>
            </select>
        </label>
        <label class="grid gap-1 text-sm font-semibold">Origin city / address
            <input type="text" disabled class="rounded-xl border border-line bg-canvas px-4 py-3 font-normal opacity-80">
        </label>
        <label class="grid gap-1 text-sm font-semibold">Destination city / address
            <input type="text" disabled class="rounded-xl border border-line bg-canvas px-4 py-3 font-normal opacity-80">
        </label>
        <label class="grid gap-1 text-sm font-semibold sm:col-span-2">Routes (From → To)
            <input type="text" disabled class="rounded-xl border border-line bg-canvas px-4 py-3 font-normal opacity-80" placeholder="Multiple route rows">
        </label>
        <label class="grid gap-1 text-sm font-semibold">Branches + contacts
            <input type="text" disabled class="rounded-xl border border-line bg-canvas px-4 py-3 font-normal opacity-80">
        </label>
        <label class="grid gap-1 text-sm font-semibold">Bank accounts
            <input type="text" disabled class="rounded-xl border border-line bg-canvas px-4 py-3 font-normal opacity-80">
        </label>
        <label class="grid gap-1 text-sm font-semibold">Credit / opening balance
            <input type="text" disabled class="rounded-xl border border-line bg-canvas px-4 py-3 font-normal opacity-80">
        </label>
        <label class="grid gap-1 text-sm font-semibold">Photos / cards
            <input type="file" disabled class="rounded-xl border border-line bg-canvas px-4 py-3 font-normal opacity-80">
        </label>
    </div>

    <div class="mt-auto flex justify-end gap-2 border-t border-line pt-4">
        <button type="button" data-window-close class="rounded-xl border border-line bg-canvas px-4 py-2 text-sm font-semibold">Close</button>
        <button type="button" disabled class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold opacity-60">Save (later)</button>
    </div>
</div>
