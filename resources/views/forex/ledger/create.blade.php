<x-layouts.shell title="Post ledger entry" :user="$user">
    @include('forex.ledger.partials.form', ['cancel' => route('forex.ledger')])
</x-layouts.shell>
