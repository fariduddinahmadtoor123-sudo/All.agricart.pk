<x-layouts.shell title="Edit color" :user="$user">
    <div class="max-w-xl overflow-hidden rounded-2xl border border-line bg-card">
        @include('colors.partials.form', ['scheme' => $scheme, 'cancel' => route('colors')])
    </div>
</x-layouts.shell>
