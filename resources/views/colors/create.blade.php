<x-layouts.shell title="New color" :user="$user">
    <div class="max-w-xl overflow-hidden rounded-2xl border border-line bg-card">
        @include('colors.partials.form', ['cancel' => route('colors')])
    </div>
</x-layouts.shell>
