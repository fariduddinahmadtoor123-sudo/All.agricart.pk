<x-layouts.shell title="New note" :user="$user">
    <div class="max-w-xl overflow-hidden rounded-2xl border border-line bg-card">
        @include('notes.partials.form', ['cancel' => route('notes')])
    </div>
</x-layouts.shell>
