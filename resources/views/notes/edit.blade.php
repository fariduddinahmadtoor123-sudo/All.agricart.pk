<x-layouts.shell title="Edit note" :user="$user">
    <div class="max-w-xl overflow-hidden rounded-2xl border border-line bg-card">
        @include('notes.partials.form', ['note' => $note, 'cancel' => route('notes')])
    </div>
</x-layouts.shell>
