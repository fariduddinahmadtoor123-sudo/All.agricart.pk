<x-layouts.shell title="Forex Pending Payments" :user="$user" fit>
    <x-slot:panel>
        <button type="button" data-window-open="payment-window" class="rounded-xl bg-card px-4 py-2 text-sm font-semibold">Add request</button>
        @foreach (['pending' => 'Pending', 'history' => 'History', 'rejected' => 'Rejected'] as $key => $label)
            <a
                href="{{ route('forex.payments', ['status' => $key]) }}"
                @class([
                    'rounded-xl px-4 py-2 text-sm font-semibold',
                    'bg-card' => $view === $key,
                    'border border-line' => $view !== $key,
                ])
                @if ($view === $key) aria-current="page" @endif
            >{{ $label }}</a>
        @endforeach
    </x-slot:panel>

    <div class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-2xl border border-line bg-card" data-arrow-pane>
        @if (session('status'))
            <p class="shrink-0 border-b border-line px-4 py-2 text-sm sm:px-6">{{ session('status') }}</p>
        @endif
        @if ($errors->has('payment'))
            <p class="shrink-0 border-b border-line px-4 py-2 text-sm sm:px-6">{{ $errors->first('payment') }}</p>
        @endif
        <button type="button" class="side-arrow w-full border-b border-line bg-soft" data-scroll-up hidden aria-label="Payments above">
            <svg viewBox="0 0 16 16" class="h-4 w-4" aria-hidden="true"><path d="M3.5 10.5 8 6l4.5 4.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path></svg>
        </button>
        <div class="arrow-scroll min-h-0 flex-1" data-arrow-scroll>
            <table class="w-full text-left text-sm">
                <thead class="sticky top-0 bg-soft">
                    <tr>
                        <th class="px-4 py-3 font-semibold sm:px-6">When</th>
                        <th class="px-4 py-3 font-semibold sm:px-6">Number</th>
                        <th class="px-4 py-3 font-semibold sm:px-6">Name</th>
                        <th class="px-4 py-3 font-semibold sm:px-6">Type</th>
                        <th class="px-4 py-3 font-semibold sm:px-6">From</th>
                        <th class="px-4 py-3 font-semibold sm:px-6">To</th>
                        <th class="px-4 py-3 text-right font-semibold sm:px-6">Amount</th>
                        <th class="px-4 py-3 font-semibold sm:px-6">Reference</th>
                        @if ($view === 'rejected')
                            <th class="px-4 py-3 font-semibold sm:px-6">Reason</th>
                        @elseif ($view === 'pending')
                            <th class="px-4 py-3 text-right font-semibold sm:px-6">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                        @php($when = $view === 'history' ? $payment->paid_at : $payment->created_at)
                        <tr class="border-t border-line">
                            <td class="px-4 py-3 tabular-nums sm:px-6">{{ $when?->timezone(config('app.timezone'))->format('d M H:i') }}</td>
                            <td class="px-4 py-3 sm:px-6">
                                <a href="{{ route('forex.ledger.show', $payment->clientAccount) }}" class="font-semibold tabular-nums hover:underline">{{ $payment->clientAccount->number }}</a>
                            </td>
                            <td class="px-4 py-3 sm:px-6">{{ $payment->clientAccount->user->name }}</td>
                            <td class="px-4 py-3 sm:px-6">{{ $payment->typeLabel() }}</td>
                            <td class="px-4 py-3 sm:px-6">{{ $payment->fromPlace() }}</td>
                            <td class="px-4 py-3 sm:px-6">{{ $payment->toPlace() }}</td>
                            <td @class([
                                'px-4 py-3 text-right font-semibold tabular-nums sm:px-6',
                                'text-emerald-800' => $payment->type === 'deposit',
                                'text-red-800' => $payment->type === 'withdrawal',
                            ])>{{ $payment->type === 'withdrawal' ? '-' : '+' }}{{ number_format((float) $payment->amount, 2, '.', ',') }}</td>
                            <td class="px-4 py-3 sm:px-6">{{ $payment->reference ?: '—' }}</td>
                            @if ($view === 'rejected')
                                <td class="px-4 py-3 sm:px-6">{{ $payment->rejection_reason ?: '—' }}</td>
                            @elseif ($view === 'pending')
                                <td class="px-4 py-3 sm:px-6">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <form method="POST" action="{{ route('forex.payments.approve', $payment) }}">
                                            @csrf
                                            <button type="submit" class="rounded-xl border border-line bg-canvas px-3 py-2 text-sm font-semibold">Approve</button>
                                        </form>
                                        <button
                                            type="button"
                                            data-window-open="reject-window"
                                            data-reject-id="{{ $payment->id }}"
                                            data-reject-url="{{ route('forex.payments.reject', $payment) }}"
                                            data-reject-who="{{ $payment->clientAccount->number }} · {{ $payment->typeLabel() }} · {{ number_format((float) $payment->amount, 2, '.', ',') }}"
                                            class="rounded-xl border border-line bg-canvas px-3 py-2 text-sm font-semibold"
                                        >Reject</button>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td class="px-4 py-8 text-ink/70 sm:px-6" colspan="{{ $view === 'history' ? 8 : 9 }}">
                                @if ($view === 'history')
                                    No approved payments yet.
                                @elseif ($view === 'rejected')
                                    No rejected requests.
                                @else
                                    No requests waiting.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <button type="button" class="side-arrow w-full border-t border-line bg-soft" data-scroll-down hidden aria-label="Payments below">
            <svg viewBox="0 0 16 16" class="h-4 w-4" aria-hidden="true"><path d="M3.5 5.5 8 10l4.5-4.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path></svg>
        </button>
    </div>

    @push('windows')
        <x-desk-window id="payment-window" title="Add request" :tab="route('forex.payments.create')" :open="$errors->any() && old('_form') === 'payment'">
            @include('forex.payments.partials.form')
        </x-desk-window>
        <x-desk-window id="reject-window" title="Reject request" :open="$errors->has('reason')">
            <form id="reject-form" method="POST" action="{{ old('payment_id') ? route('forex.payments.reject', old('payment_id')) : route('forex.payments') }}" class="flex h-full flex-col gap-4 p-4 sm:gap-6 sm:p-6">
                @csrf
                <input type="hidden" name="_form" value="reject">
                <input type="hidden" name="payment_id" value="{{ old('payment_id') }}">
                <p id="reject-who" class="text-sm text-ink/70">Say why this request is rejected. The customer will see this reason.</p>
                <label class="flex flex-col gap-2 text-sm font-semibold">
                    Reason
                    <textarea name="reason" required maxlength="255" rows="3" class="rounded-xl border border-line bg-canvas px-4 py-3 text-base font-normal outline-none focus:border-ink">{{ old('reason') }}</textarea>
                    @error('reason')
                        <span class="text-sm font-normal">{{ $message }}</span>
                    @enderror
                </label>
                <div class="mt-auto flex justify-end gap-2 border-t border-line pt-4">
                    <button type="button" data-window-close class="rounded-xl border border-line bg-canvas px-4 py-2 text-sm font-semibold">Cancel</button>
                    <button type="submit" class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold">Reject</button>
                </div>
            </form>
        </x-desk-window>
        <script>
            document.querySelectorAll('[data-reject-url]').forEach((button) => {
                button.addEventListener('click', () => {
                    const form = document.getElementById('reject-form');
                    const who = document.getElementById('reject-who');

                    if (! form) {
                        return;
                    }

                    form.action = button.dataset.rejectUrl;
                    form.querySelector('[name="payment_id"]').value = button.dataset.rejectId;
                    who.textContent = button.dataset.rejectWho + '. Say why this request is rejected.';
                    form.querySelector('[name="reason"]').value = '';
                });
            });
        </script>
    @endpush
</x-layouts.shell>
