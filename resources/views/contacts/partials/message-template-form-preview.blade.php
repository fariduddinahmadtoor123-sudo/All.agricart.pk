<div class="flex h-full flex-col gap-5 p-6">
    @include('contacts.partials.ui-preview-note')

    <div class="grid gap-4">
        <label class="grid gap-1 text-sm font-semibold">Title
            <input type="text" disabled class="rounded-xl border border-line bg-canvas px-4 py-3 font-normal opacity-80">
        </label>
        <label class="grid gap-1 text-sm font-semibold">Message (EN)
            <textarea disabled rows="4" class="rounded-xl border border-line bg-canvas px-4 py-3 font-normal opacity-80"></textarea>
        </label>
        <label class="grid gap-1 text-sm font-semibold">Message (UR)
            <textarea disabled rows="4" dir="rtl" class="rounded-xl border border-line bg-canvas px-4 py-3 font-normal opacity-80"></textarea>
        </label>
    </div>

    <div class="mt-auto flex justify-end gap-2 border-t border-line pt-4">
        <button type="button" data-window-close class="rounded-xl border border-line bg-canvas px-4 py-2 text-sm font-semibold">Close</button>
        <button type="button" disabled class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold opacity-60">Save (later)</button>
    </div>
</div>
