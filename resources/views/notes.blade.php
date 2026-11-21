<x-layouts.shell title="Notes" :user="$user">
    <x-slot:panel>
        <button type="button" data-window-open="note-window" class="rounded-xl bg-card px-4 py-2 text-sm font-semibold">Add new note</button>
    </x-slot:panel>

    <div class="overflow-hidden rounded-2xl border border-line bg-card">
        <table class="w-full text-left text-sm">
            <thead class="bg-soft">
                <tr>
                    <th class="px-6 py-3 font-semibold">Title</th>
                    <th class="px-6 py-3 font-semibold">Text</th>
                    <th class="px-6 py-3 text-right font-semibold">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($notes as $note)
                    <tr class="border-t border-line">
                        <td class="px-6 py-4 align-top font-semibold">{{ $note->title }}</td>
                        <td class="px-6 py-4 align-top">
                            <p data-note-body dir="auto" class="max-w-xl whitespace-pre-wrap">{{ $note->body }}</p>
                        </td>
                        <td class="px-6 py-4 align-top">
                            <div class="flex items-center justify-end gap-2">
                                <button type="button" data-copy-note class="rounded-xl border border-line bg-canvas px-3 py-2 text-sm font-semibold">Copy</button>
                                <button type="button" data-window-open="note-window-{{ $note->id }}" class="rounded-xl border border-line bg-canvas px-3 py-2 text-sm font-semibold">Edit</button>
                                <form method="POST" action="{{ route('notes.destroy', $note) }}" onsubmit="return confirm('Delete this note?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-xl border border-line bg-canvas px-3 py-2 text-sm font-semibold">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-6 py-10 text-ink/70" colspan="3">No notes yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @push('windows')
        <x-desk-window id="note-window" title="New note" :tab="route('notes.create')" :open="$errors->any() && old('_form') === 'create'">
            @include('notes.partials.form')
        </x-desk-window>
        @foreach ($notes as $note)
            <x-desk-window id="note-window-{{ $note->id }}" title="Edit note" :tab="route('notes.edit', $note)" :open="$errors->any() && (string) old('_form') === (string) $note->id">
                @include('notes.partials.form', ['note' => $note])
            </x-desk-window>
        @endforeach
    @endpush
</x-layouts.shell>
