<x-layouts.shell title="New role" :user="$user">
    <div class="max-w-xl overflow-hidden rounded-2xl border border-line bg-card">
        @include('roles.partials.form', ['cancel' => route('roles')])
    </div>
</x-layouts.shell>
