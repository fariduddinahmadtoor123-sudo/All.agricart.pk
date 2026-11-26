<x-layouts.shell title="Customers" :user="$user">
    <x-slot:panel>
        <button type="button" data-window-open="customer-window" class="rounded-xl bg-card px-4 py-2 text-sm font-semibold">Add customer</button>
    </x-slot:panel>

    <div class="overflow-hidden rounded-2xl border border-line bg-card">
        <table class="w-full text-left text-sm">
            <thead class="bg-soft">
                <tr>
                    <th class="px-4 py-3 font-semibold sm:px-6">Name</th>
                    <th class="px-4 py-3 font-semibold sm:px-6">Phone</th>
                    <th class="px-4 py-3 font-semibold sm:px-6">City</th>
                    <th class="px-4 py-3 font-semibold sm:px-6">Channel</th>
                    <th class="px-4 py-3 font-semibold sm:px-6">Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="px-4 py-10 text-ink/70 sm:px-6" colspan="5">No customers yet. Open Add customer to preview the form UI.</td>
                </tr>
            </tbody>
        </table>
    </div>

    @push('windows')
        <x-desk-window id="customer-window" title="New customer">
            @include('contacts.partials.customer-form-preview')
        </x-desk-window>
    @endpush
</x-layouts.shell>
