<x-layouts.shell title="New MT5 account" :user="$user">
    @include('forex.mt5-accounts.partials.form', ['cancel' => route('forex.mt5-accounts')])
</x-layouts.shell>
