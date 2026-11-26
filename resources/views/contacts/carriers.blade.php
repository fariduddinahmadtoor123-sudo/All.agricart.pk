<x-layouts.shell title="Carriers" :user="$user">
    <x-slot:panel>
        <button type="button" data-window-open="carrier-window" class="rounded-xl bg-card px-4 py-2 text-sm font-semibold">Add carrier</button>
    </x-slot:panel>

    <div class="overflow-hidden rounded-2xl border border-line bg-card">
        <table class="w-full text-left text-sm">
            <thead class="bg-soft">
                <tr>
                    <th class="px-4 py-3 font-semibold sm:px-6">Code</th>
                    <th class="px-4 py-3 font-semibold sm:px-6">Business</th>
                    <th class="px-4 py-3 font-semibold sm:px-6">Contact</th>
                    <th class="px-4 py-3 font-semibold sm:px-6">City</th>
                    <th class="px-4 py-3 font-semibold sm:px-6">Type</th>
                    <th class="px-4 py-3 font-semibold sm:px-6">Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="px-4 py-10 text-ink/70 sm:px-6" colspan="6">No carriers yet. Open Add carrier to preview the form UI.</td>
                </tr>
            </tbody>
        </table>
    </div>

    @push('windows')
        <x-desk-window id="carrier-window" title="New carrier">
            @include('contacts.partials.carrier-form-preview', ['transportTypes' => $transportTypes])
        </x-desk-window>
    @endpush
</x-layouts.shell>
