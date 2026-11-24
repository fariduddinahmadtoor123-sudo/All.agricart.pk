@php($back = $page ?? false)
@if ($clientAccount === null)
    <p class="p-4 text-sm text-ink/70 sm:p-6">This login has no client account yet.</p>
@else
    <div class="grid gap-6 p-4 sm:p-6">
        <p class="text-sm text-ink/70">{{ $clientAccount->number }} — account {{ number_format((float) $clientAccount->balance, 2, '.', ',') }} · wallet {{ number_format((float) ($wallet->balance ?? 0), 2, '.', ',') }}. Deposit comes out of the wallet. Withdrawal goes into the wallet.</p>
        @foreach (['deposit' => 'Deposit', 'withdrawal' => 'Withdrawal'] as $kind => $label)
            @php($form = 'terminal-'.$kind)
            <form method="POST" action="{{ route('forex.market-watch.payments.store') }}" class="grid gap-3 rounded-xl border border-line p-4">
                @csrf
                <input type="hidden" name="_form" value="{{ $form }}">
                <input type="hidden" name="type" value="{{ $kind }}">
                @if ($back)
                    <input type="hidden" name="return" value="page">
                @endif
                <p class="text-sm font-semibold">{{ $label }}</p>
                <label class="flex flex-col gap-1 text-sm font-semibold">
                    Amount
                    <input name="amount" type="number" min="0.01" step="0.01" value="{{ old('_form') === $form ? old('amount') : '' }}" required class="rounded-xl border border-line bg-canvas px-4 py-3 text-base font-normal outline-none focus:border-ink">
                    @if (old('_form') === $form)
                        @error('amount')
                            <span class="text-sm font-normal">{{ $message }}</span>
                        @enderror
                    @endif
                </label>
                <div class="flex justify-end">
                    <button type="submit" class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold">{{ $label }}</button>
                </div>
            </form>
        @endforeach
    </div>
@endif
