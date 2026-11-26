<div class="flex h-full flex-col gap-5 p-6">
    @include('contacts.partials.ui-preview-note')

    <div class="grid gap-4 sm:grid-cols-2">
        <label class="grid gap-1 text-sm font-semibold">Display name *
            <input type="text" disabled class="rounded-xl border border-line bg-canvas px-4 py-3 font-normal opacity-80" placeholder="Required">
        </label>
        <label class="grid gap-1 text-sm font-semibold">Channel
            <select disabled class="rounded-xl border border-line bg-canvas px-4 py-3 font-normal opacity-80">
                <option>Offline</option>
                <option>Online</option>
            </select>
        </label>
        <label class="grid gap-1 text-sm font-semibold">Name (EN)
            <input type="text" disabled class="rounded-xl border border-line bg-canvas px-4 py-3 font-normal opacity-80">
        </label>
        <label class="grid gap-1 text-sm font-semibold">Name (UR)
            <input type="text" disabled dir="rtl" class="rounded-xl border border-line bg-canvas px-4 py-3 font-normal opacity-80">
        </label>
        <label class="grid gap-1 text-sm font-semibold sm:col-span-2">Phone *
            <input type="text" disabled class="rounded-xl border border-line bg-canvas px-4 py-3 font-normal opacity-80" placeholder="At least one mobile">
        </label>
        <label class="grid gap-1 text-sm font-semibold">Address (EN)
            <textarea disabled rows="2" class="rounded-xl border border-line bg-canvas px-4 py-3 font-normal opacity-80"></textarea>
        </label>
        <label class="grid gap-1 text-sm font-semibold">Address (UR)
            <textarea disabled rows="2" dir="rtl" class="rounded-xl border border-line bg-canvas px-4 py-3 font-normal opacity-80"></textarea>
        </label>
        <label class="grid gap-1 text-sm font-semibold">City
            <input type="text" disabled class="rounded-xl border border-line bg-canvas px-4 py-3 font-normal opacity-80" placeholder="Pakistani city list">
        </label>
        <label class="grid gap-1 text-sm font-semibold">Email
            <input type="email" disabled class="rounded-xl border border-line bg-canvas px-4 py-3 font-normal opacity-80">
        </label>
        <label class="grid gap-1 text-sm font-semibold">Opening balance
            <input type="text" disabled class="rounded-xl border border-line bg-canvas px-4 py-3 font-normal opacity-80">
        </label>
        <label class="grid gap-1 text-sm font-semibold">Credit limit
            <input type="text" disabled class="rounded-xl border border-line bg-canvas px-4 py-3 font-normal opacity-80">
        </label>
        <label class="grid gap-1 text-sm font-semibold">Profile photo
            <input type="file" disabled class="rounded-xl border border-line bg-canvas px-4 py-3 font-normal opacity-80">
        </label>
        <label class="flex items-center gap-2 text-sm font-semibold">
            <input type="checkbox" disabled checked> Active
        </label>
    </div>

    <div class="mt-auto flex justify-end gap-2 border-t border-line pt-4">
        <button type="button" data-window-close class="rounded-xl border border-line bg-canvas px-4 py-2 text-sm font-semibold">Close</button>
        <button type="button" disabled class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold opacity-60">Save (later)</button>
    </div>
</div>
