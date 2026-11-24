<x-layouts.shell title="Forex Customer Ledgers" :user="$user" fit>
    <div class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-2xl border border-line bg-card" data-arrow-pane>
        <button type="button" class="side-arrow w-full border-b border-line bg-soft" data-scroll-up hidden aria-label="Ledgers above">
            <svg viewBox="0 0 16 16" class="h-4 w-4" aria-hidden="true"><path d="M3.5 10.5 8 6l4.5 4.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path></svg>
        </button>
        <div class="arrow-scroll min-h-0 flex-1" data-arrow-scroll>
            <table class="w-full text-left text-sm">
                <thead class="sticky top-0 bg-soft">
                    <tr>
                        <th class="px-4 py-3 font-semibold sm:px-6">Number</th>
                        <th class="px-4 py-3 font-semibold sm:px-6">Name</th>
                        <th class="hidden px-4 py-3 font-semibold sm:table-cell sm:px-6">MT5</th>
                        <th class="px-4 py-3 text-right font-semibold sm:px-6">Opening balance</th>
                        <th class="px-4 py-3 text-right font-semibold sm:px-6">Profit</th>
                        <th class="px-4 py-3 text-right font-semibold sm:px-6">Wallet</th>
                        <th class="px-4 py-3 text-right font-semibold sm:px-6">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($accounts as $account)
                        <tr class="border-t border-line">
                            <td class="px-4 py-3 sm:px-6">
                                <a href="{{ route('forex.ledger.show', $account) }}" class="font-semibold tabular-nums hover:underline">{{ $account->number }}</a>
                            </td>
                            <td class="px-4 py-3 sm:px-6">{{ $account->user->name }}</td>
                            <td class="hidden px-4 py-3 sm:table-cell sm:px-6">{{ $account->mt5Account?->name ?: '—' }}</td>
                            <td class="px-4 py-3 text-right tabular-nums sm:px-6">{{ $figures[$account->id]['opening'] }}</td>
                            <td @class([
                                'px-4 py-3 text-right font-semibold tabular-nums sm:px-6',
                                'text-emerald-800' => $figures[$account->id]['tone'] === 'profit',
                                'text-red-800' => $figures[$account->id]['tone'] === 'loss',
                            ])>{{ $figures[$account->id]['profit'] }}</td>
                            <td class="px-4 py-3 text-right tabular-nums sm:px-6">{{ number_format((float) ($account->user->wallet->balance ?? 0), 2, '.', ',') }}</td>
                            <td class="px-4 py-3 text-right tabular-nums sm:px-6">{{ number_format((float) $account->balance, 2, '.', ',') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="px-4 py-8 text-ink/70 sm:px-6" colspan="7">No client accounts yet. Open one under Client accounts first.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <button type="button" class="side-arrow w-full border-t border-line bg-soft" data-scroll-down hidden aria-label="Ledgers below">
            <svg viewBox="0 0 16 16" class="h-4 w-4" aria-hidden="true"><path d="M3.5 5.5 8 10l4.5-4.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path></svg>
        </button>
    </div>

</x-layouts.shell>
