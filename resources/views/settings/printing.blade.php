<x-layouts.shell title="Printing" :user="$user">
    <x-slot:panel>
        <button type="button" data-window-open="print-profile-window" class="rounded-xl bg-card px-4 py-2 text-sm font-semibold">Add print size</button>
    </x-slot:panel>

    @if (session('status'))
        <p class="mb-4 text-sm">{{ session('status') }}</p>
    @endif

    <p class="mb-6 text-sm text-ink/70">One print center for Desk. Documents (A3–A6, Letter, Legal), receipt rolls, and barcode / label stickers. Later every printable page will use these profiles.</p>

    @include('settings.printing.partials.profile-table', [
        'heading' => 'Documents',
        'profiles' => $documents,
        'empty' => 'No document sizes yet.',
    ])

    @include('settings.printing.partials.profile-table', [
        'heading' => 'Receipts',
        'profiles' => $receipts,
        'empty' => 'No receipt sizes yet.',
    ])

    @include('settings.printing.partials.profile-table', [
        'heading' => 'Labels & barcodes',
        'profiles' => $labels,
        'empty' => 'No label sizes yet.',
        'barcode' => true,
    ])

    @push('windows')
        <x-desk-window id="print-profile-window" title="New print size" :open="$errors->any() && old('_form') === 'create'">
            @include('settings.printing.partials.form')
        </x-desk-window>

        @foreach ($documents->concat($receipts)->concat($labels) as $profile)
            <x-desk-window id="print-profile-window-{{ $profile->id }}" title="Edit {{ $profile->localizedName() }}" :open="$errors->any() && (string) old('_form') === (string) $profile->id">
                @include('settings.printing.partials.form', ['profile' => $profile])
            </x-desk-window>
        @endforeach
    @endpush
</x-layouts.shell>
