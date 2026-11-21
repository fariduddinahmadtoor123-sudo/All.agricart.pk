<x-layouts.shell title="Roles" :user="$user">
    <x-slot:panel>
        <button type="button" data-window-open="role-window" class="rounded-xl bg-card px-4 py-2 text-sm font-semibold">Add new role</button>
    </x-slot:panel>

    <div class="overflow-hidden rounded-2xl border border-line bg-card">
        <table class="w-full text-left text-sm">
            <thead class="bg-soft">
                <tr>
                    <th class="px-6 py-3 font-semibold">Name</th>
                    <th class="px-6 py-3 font-semibold">Description</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($roles as $role)
                    <tr class="border-t border-line">
                        <td class="px-6 py-4 font-semibold">{{ $role->name }}</td>
                        <td class="px-6 py-4">{{ $role->description }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-6 py-10 text-ink/70" colspan="2">No roles yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @push('windows')
        <x-desk-window id="role-window" title="New role" :tab="route('roles.create')" :open="$errors->any()">
            @include('roles.partials.form')
        </x-desk-window>
    @endpush
</x-layouts.shell>
