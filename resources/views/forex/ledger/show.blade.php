<x-layouts.shell :title="$account->number" :user="$user" fit>
    <x-slot:panel>
        <button type="button" data-window-open="wallet-deposit" class="rounded-xl bg-card px-4 py-2 text-sm font-semibold">Deposit to wallet</button>
    </x-slot:panel>
    @if (session('status'))
        <p class="mb-3 text-sm">{{ session('status') }}</p>
    @endif
    <div class="mb-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">
        <p class="font-semibold">{{ $account->user->name }}</p>
        <p class="tabular-nums">Balance {{ number_format((float) $account->balance, 2, '.', ',') }}</p>
        <p class="tabular-nums">Wallet {{ number_format((float) $wallet->balance, 2, '.', ',') }}</p>
        <p>{{ $account->mt5Account?->name ?: 'No MT5 account' }}</p>
    </div>

    <div class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-2xl border border-line bg-card" data-arrow-pane>
        <div class="shrink-0 border-b border-line bg-soft px-4 py-2 text-sm font-semibold sm:px-6">Forex Customer Ledger</div>
        <button type="button" class="side-arrow w-full border-b border-line bg-soft" data-scroll-up hidden aria-label="Ledger above">
            <svg viewBox="0 0 16 16" class="h-4 w-4" aria-hidden="true"><path d="M3.5 10.5 8 6l4.5 4.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path></svg>
        </button>
        <div class="arrow-scroll min-h-0 flex-1" data-arrow-scroll>
            <table class="w-full text-left text-sm">
                <thead class="sticky top-0 bg-soft">
                    <tr>
                        <th class="px-4 py-3 font-semibold sm:px-6">Opened</th>
                        <th class="px-4 py-3 font-semibold sm:px-6">Closed</th>
                        <th class="px-4 py-3 font-semibold sm:px-6">Type</th>
                        <th class="px-4 py-3 font-semibold sm:px-6">From</th>
                        <th class="px-4 py-3 font-semibold sm:px-6">To</th>
                        <th class="px-4 py-3 font-semibold sm:px-6">Ticket</th>
                        <th class="px-4 py-3 font-semibold sm:px-6">MT5</th>
                        <th class="px-4 py-3 text-right font-semibold sm:px-6">Amount</th>
                        <th class="hidden px-4 py-3 text-right font-semibold sm:table-cell sm:px-6">Balance after</th>
                        <th class="hidden px-4 py-3 font-semibold md:table-cell sm:px-6">Note</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($lines as $line)
                        <tr class="border-t border-line">
                            <td class="px-4 py-3 tabular-nums sm:px-6">{{ $line['opened_at']?->timezone(config('app.timezone'))->format('d M H:i') ?? '—' }}</td>
                            <td class="px-4 py-3 tabular-nums sm:px-6">{{ $line['closed_at']?->timezone(config('app.timezone'))->format('d M H:i') ?? '—' }}</td>
                            <td class="px-4 py-3 sm:px-6">{{ $line['type'] }}</td>
                            <td class="px-4 py-3 sm:px-6">{{ $line['from'] !== '' ? $line['from'] : '—' }}</td>
                            <td class="px-4 py-3 sm:px-6">{{ $line['to'] !== '' ? $line['to'] : '—' }}</td>
                            <td class="px-4 py-3 tabular-nums sm:px-6">{{ $line['desk_ticket'] ?: '—' }}</td>
                            <td class="px-4 py-3 tabular-nums sm:px-6">{{ $line['mt5_ticket'] ?: '—' }}</td>
                            <td class="px-4 py-3 text-right tabular-nums sm:px-6 {{ $line['amount'] < 0 ? 'text-red-700' : '' }}">
                                {{ number_format($line['amount'], 2, '.', ',') }}
                            </td>
                            <td class="hidden px-4 py-3 text-right tabular-nums sm:table-cell sm:px-6">{{ number_format($line['balance_after'], 2, '.', ',') }}</td>
                            <td class="hidden px-4 py-3 md:table-cell sm:px-6">{{ $line['note'] !== '' ? $line['note'] : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="px-4 py-8 text-ink/70 sm:px-6" colspan="10">No ledger entries yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <button type="button" class="side-arrow w-full border-t border-line bg-soft" data-scroll-down hidden aria-label="Ledger below">
            <svg viewBox="0 0 16 16" class="h-4 w-4" aria-hidden="true"><path d="M3.5 5.5 8 10l4.5-4.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path></svg>
        </button>
    </div>

    @push('windows')
        <x-desk-window id="wallet-deposit" title="Deposit to wallet" :open="$errors->any()">
            <form method="POST" action="{{ route('forex.ledger.wallet', $account) }}" class="grid gap-3 p-4 sm:p-6">
                @csrf
                <p class="text-sm text-ink/70">This adds money to {{ $account->user->name }}'s wallet. From Desk, to Wallet.</p>
                <label class="flex flex-col gap-1 text-sm font-semibold">
                    Amount
                    <input name="amount" type="number" min="0.01" step="0.01" value="{{ old('amount') }}" required class="rounded-xl border border-line bg-canvas px-4 py-3 text-base font-normal outline-none focus:border-ink">
                    @error('amount')
                        <span class="text-sm font-normal">{{ $message }}</span>
                    @enderror
                </label>
                <div class="flex justify-end">
                    <button type="submit" class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold">Deposit to wallet</button>
                </div>
            </form>
        </x-desk-window>
    @endpush
</x-layouts.shell>
