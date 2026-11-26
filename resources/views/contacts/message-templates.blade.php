<x-layouts.shell title="Message templates" :user="$user">
    <x-slot:panel>
        <button type="button" data-window-open="template-window" class="rounded-xl bg-card px-4 py-2 text-sm font-semibold">Add template</button>
    </x-slot:panel>

    <div class="overflow-hidden rounded-2xl border border-line bg-card">
        <table class="w-full text-left text-sm">
            <thead class="bg-soft">
                <tr>
                    <th class="px-4 py-3 font-semibold sm:px-6">Title</th>
                    <th class="px-4 py-3 font-semibold sm:px-6">Language</th>
                    <th class="px-4 py-3 font-semibold sm:px-6">Preview</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="px-4 py-10 text-ink/70 sm:px-6" colspan="3">No message templates yet. Open Add template to preview the form UI.</td>
                </tr>
            </tbody>
        </table>
    </div>

    @push('windows')
        <x-desk-window id="template-window" title="New message template">
            @include('contacts.partials.message-template-form-preview')
        </x-desk-window>
    @endpush
</x-layouts.shell>
