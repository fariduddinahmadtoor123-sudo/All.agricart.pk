<x-layouts.shell title="Appearance" :user="$user">
    <x-slot:panel>
        <button type="button" data-window-open="color-window" class="rounded-xl bg-card px-4 py-2 text-sm font-semibold">Add new color</button>
    </x-slot:panel>

    <x-theme-switch :schemes="$schemes" />

    <section class="mt-8" aria-label="Corners">
        <h2 class="text-sm font-semibold">Corners</h2>
        <div class="mt-3">
            <x-corner-switch />
        </div>
    </section>

    @push('windows')
        <x-desk-window id="color-window" title="New color" :tab="route('colors.create')" :open="$errors->any() && old('_form') === 'create'">
            @include('colors.partials.form')
        </x-desk-window>
        @foreach ($schemes as $scheme)
            <x-desk-window id="color-window-{{ $scheme->id }}" title="Edit color" :tab="route('colors.edit', $scheme)" :open="$errors->any() && (string) old('_form') === (string) $scheme->id">
                @include('colors.partials.form', ['scheme' => $scheme])
            </x-desk-window>
        @endforeach
    @endpush
</x-layouts.shell>
