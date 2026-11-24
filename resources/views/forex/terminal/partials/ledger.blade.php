@if ($clientAccount === null)
    <p class="p-4 text-sm text-ink/70 sm:p-6">This login has no client account yet.</p>
@else
    <div class="flex h-full flex-col gap-4 p-4 sm:p-6">
        <p class="text-sm">
            <span class="font-semibold">{{ $clientAccount->number }}</span>
            <span class="text-ink/70">{{ $clientAccount->user->name }}</span>
            <span class="tabular-nums">Balance {{ number_format((float) $clientAccount->balance, 2, '.', ',') }}</span>
            <span class="tabular-nums">Wallet {{ number_format((float) ($wallet->balance ?? 0), 2, '.', ',') }}</span>
        </p>
        <p class="text-sm">
            <span class="text-ink/70">Profit</span>
            <span @class([
                'font-semibold tabular-nums',
                'text-emerald-800' => $profit['tone'] === 'profit',
                'text-red-800' => $profit['tone'] === 'loss',
            ])>{{ $profit['profit'] }}</span>
            <span class="text-ink/60" data-ledger-opened>as of {{ $openedAt->timezone(config('app.timezone'))->format('d M H:i') }}</span>
        </p>
        <p class="text-sm text-ink/70">Closed trades up to this open. Live orders stay in the orders list.</p>
        @if ($paymentRequests->isNotEmpty())
            <div class="overflow-hidden rounded-xl border border-line">
                <p class="border-b border-line bg-soft px-3 py-2 text-xs font-semibold">Waiting and rejected</p>
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr>
                            <th class="px-3 py-2 font-semibold">When</th>
                            <th class="px-3 py-2 font-semibold">Type</th>
                            <th class="px-3 py-2 text-right font-semibold">Amount</th>
                            <th class="px-3 py-2 font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($paymentRequests as $payment)
                            <tr class="border-t border-line">
                                <td class="px-3 py-2 tabular-nums">{{ $payment->created_at?->timezone(config('app.timezone'))->format('d M H:i') }}</td>
                                <td class="px-3 py-2">{{ $payment->typeLabel() }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ number_format((float) $payment->amount, 2, '.', ',') }}</td>
                                <td class="px-3 py-2">
                                    {{ $payment->statusLabel() }}
                                    @if ($payment->rejection_reason)
                                        <span class="mt-0.5 block text-xs text-ink/70">{{ $payment->rejection_reason }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        <div class="overflow-hidden rounded-xl border border-line">
            <table class="w-full text-left text-sm">
                <thead class="bg-soft">
                    <tr>
                        <th class="px-3 py-2 font-semibold">When</th>
                        <th class="px-3 py-2 font-semibold">Type</th>
                        <th class="px-3 py-2 text-right font-semibold">Amount</th>
                        <th class="px-3 py-2 text-right font-semibold">Balance after</th>
                        <th class="px-3 py-2 font-semibold">Note</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ledgerLines as $line)
                        <tr class="border-t border-line">
                            <td class="px-3 py-2 tabular-nums">{{ $line['opened_at']?->timezone(config('app.timezone'))->format('d M H:i') ?? '—' }}</td>
                            <td class="px-3 py-2">{{ $line['type'] }}</td>
                            <td @class([
                                'px-3 py-2 text-right font-semibold tabular-nums',
                                'text-emerald-800' => $line['amount'] > 0,
                                'text-red-800' => $line['amount'] < 0,
                            ])>{{ $line['amount'] > 0 ? '+' : '' }}{{ number_format($line['amount'], 2, '.', ',') }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ number_format($line['balance_after'], 2, '.', ',') }}</td>
                            <td class="px-3 py-2">{{ $line['note'] !== '' ? $line['note'] : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="px-3 py-6 text-ink/70" colspan="5">No payments on this ledger yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif
