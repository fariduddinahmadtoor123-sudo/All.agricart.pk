<x-layouts.shell title="Add request" :user="$user">
    @include('forex.payments.partials.form', ['cancel' => route('forex.payments')])
</x-layouts.shell>
