<x-layouts.shell title="Forex Wallet" :user="$user" fit>
    <x-slot:panel>
        <a href="{{ route('forex.market-watch') }}" class="rounded-xl bg-card px-4 py-2 text-sm font-semibold">Trade Terminal</a>
    </x-slot:panel>
    <div class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-2xl border border-line bg-card" data-arrow-pane>
        <button type="button" class="side-arrow w-full border-b border-line bg-soft" data-scroll-up hidden aria-label="Wallet above">
            <svg viewBox="0 0 16 16" class="h-4 w-4" aria-hidden="true"><path d="M3.5 10.5 8 6l4.5 4.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path></svg>
        </button>
        <div class="arrow-scroll flex min-h-0 flex-1 flex-col gap-3 p-3" data-arrow-scroll>
        @if (session('status'))
            <p class="shrink-0 rounded-xl border border-line bg-card px-4 py-2 text-sm">{{ session('status') }}</p>
        @endif
        <div class="grid shrink-0 gap-3 lg:grid-cols-3">
            <div class="rounded-2xl border border-line bg-card p-4">
                <p class="text-sm text-ink/70">Wallet balance</p>
                <p class="text-2xl font-semibold tabular-nums">{{ number_format((float) $wallet->balance, 2, '.', ',') }}</p>
                @if ($clientAccount)
                    <p class="mt-2 text-sm text-ink/70">{{ $clientAccount->number }} · account {{ number_format((float) $clientAccount->balance, 2, '.', ',') }}</p>
                @else
                    <p class="mt-2 text-sm text-ink/70">This login has no client account yet.</p>
                @endif
            </div>
            @if ($clientAccount)
                <form method="POST" action="{{ route('personal-vault.to-wallet') }}" class="grid content-start gap-3 rounded-2xl border border-line bg-card p-4">
                    @csrf
                    <p class="text-sm font-semibold">To wallet</p>
                    <p class="text-sm text-ink/70">From the trading account into this wallet.</p>
                    <label class="flex flex-col gap-1 text-sm font-semibold">
                        Amount
                        <input name="amount" type="number" min="0.01" step="0.01" required class="rounded-xl border border-line bg-canvas px-4 py-3 text-base font-normal outline-none focus:border-ink">
                    </label>
                    <div class="flex justify-end">
                        <button type="submit" class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold">To wallet</button>
                    </div>
                </form>
                <form method="POST" action="{{ route('personal-vault.to-account') }}" class="grid content-start gap-3 rounded-2xl border border-line bg-card p-4">
                    @csrf
                    <p class="text-sm font-semibold">To account</p>
                    <p class="text-sm text-ink/70">From this wallet into the trading account.</p>
                    <label class="flex flex-col gap-1 text-sm font-semibold">
                        Amount
                        <input name="amount" type="number" min="0.01" step="0.01" required class="rounded-xl border border-line bg-canvas px-4 py-3 text-base font-normal outline-none focus:border-ink">
                    </label>
                    <div class="flex justify-end">
                        <button type="submit" class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold">To account</button>
                    </div>
                </form>
            @endif
        </div>
        @if ($errors->any())
            <p class="shrink-0 text-sm">{{ $errors->first() }}</p>
        @endif
        @if ($clientAccount)
            <form method="POST" action="{{ route('personal-vault.withdraw') }}" class="flex shrink-0 flex-wrap items-end gap-3 rounded-2xl border border-line bg-card p-4">
                @csrf
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold">Request withdrawal</p>
                    <p class="text-sm text-ink/70">Ask for money to leave the wallet. It stays pending until it is approved.</p>
                </div>
                <label class="flex flex-col gap-1 text-sm font-semibold">
                    Amount
                    <input name="amount" type="number" min="0.01" step="0.01" required class="w-40 rounded-xl border border-line bg-canvas px-4 py-3 text-base font-normal outline-none focus:border-ink">
                </label>
                <button type="submit" class="rounded-xl border border-line bg-canvas px-4 py-2 text-sm font-semibold">Request withdrawal</button>
            </form>
        @endif
        @if ($requests->isNotEmpty())
            <div class="shrink-0 overflow-hidden rounded-2xl border border-line bg-card">
                <p class="border-b border-line bg-soft px-4 py-2 text-sm font-semibold">Waiting</p>
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 font-semibold">When</th>
                            <th class="px-4 py-2 font-semibold">From</th>
                            <th class="px-4 py-2 font-semibold">To</th>
                            <th class="px-4 py-2 text-right font-semibold">Amount</th>
                            <th class="px-4 py-2 font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($requests as $payment)
                            <tr class="border-t border-line">
                                <td class="px-4 py-2 tabular-nums">{{ $payment->created_at?->timezone(config('app.timezone'))->format('d M H:i') }}</td>
                                <td class="px-4 py-2">{{ $payment->fromPlace() }}</td>
                                <td class="px-4 py-2">{{ $payment->toPlace() }}</td>
                                <td class="px-4 py-2 text-right tabular-nums">{{ number_format((float) $payment->amount, 2, '.', ',') }}</td>
                                <td class="px-4 py-2">{{ $payment->statusLabel() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        <div class="border-t border-line">
                <table class="w-full text-left text-sm">
                    <thead class="sticky top-0 bg-soft">
                        <tr>
                            <th class="px-4 py-3 font-semibold sm:px-6">When</th>
                            <th class="px-4 py-3 font-semibold sm:px-6">From</th>
                            <th class="px-4 py-3 font-semibold sm:px-6">To</th>
                            <th class="px-4 py-3 text-right font-semibold sm:px-6">Amount</th>
                            <th class="px-4 py-3 text-right font-semibold sm:px-6">Wallet after</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($entries as $entry)
                            <tr class="border-t border-line">
                                <td class="px-4 py-3 tabular-nums sm:px-6">{{ $entry->created_at?->timezone(config('app.timezone'))->format('d M H:i') }}</td>
                                <td class="px-4 py-3 sm:px-6">{{ $entry->from_place }}</td>
                                <td class="px-4 py-3 sm:px-6">{{ $entry->to_place }}</td>
                                <td @class([
                                    'px-4 py-3 text-right font-semibold tabular-nums sm:px-6',
                                    'text-emerald-800' => $entry->amount > 0,
                                    'text-red-800' => $entry->amount < 0,
                                ])>{{ $entry->amount > 0 ? '+' : '' }}{{ number_format((float) $entry->amount, 2, '.', ',') }}</td>
                                <td class="px-4 py-3 text-right tabular-nums sm:px-6">{{ number_format((float) $entry->balance_after, 2, '.', ',') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-8 text-ink/70 sm:px-6" colspan="5">No wallet movements yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
        </div>
        </div>
        <button type="button" class="side-arrow w-full border-t border-line bg-soft" data-scroll-down hidden aria-label="Wallet below">
            <svg viewBox="0 0 16 16" class="h-4 w-4" aria-hidden="true"><path d="M3.5 5.5 8 10l4.5-4.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path></svg>
        </button>
    </div>
</x-layouts.shell>
