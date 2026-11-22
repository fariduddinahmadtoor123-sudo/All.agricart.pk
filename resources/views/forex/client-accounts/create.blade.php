<x-layouts.shell title="Open account" :user="$user">
    @include('forex.client-accounts.partials.form', ['cancel' => route('forex.client-accounts')])
</x-layouts.shell>
