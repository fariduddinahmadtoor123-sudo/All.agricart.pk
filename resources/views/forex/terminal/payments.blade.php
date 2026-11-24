<x-layouts.shell title="Payment requests" :user="$user">
    @if (session('status'))
        <p class="mb-4 text-sm">{{ session('status') }}</p>
    @endif
    @include('forex.terminal.partials.payments', ['page' => true])
</x-layouts.shell>
