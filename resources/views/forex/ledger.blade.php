<x-layouts.shell title="Ledger" :user="$user" fit>
    <x-slot:panel>
        <button type="button" data-window-open="ledger-window" class="rounded-xl bg-card px-4 py-2 text-sm font-semibold">Post entry</button>
    </x-slot:panel>

    <div class="flex min-h-0 flex-1 flex-col gap-3">
        <div class="flex max-h-[40%] min-h-40 flex-col overflow-hidden rounded-2xl border border-line bg-card" data-arrow-pane>
            <div class="shrink-0 border-b border-line bg-soft px-4 py-2 text-sm font-semibold sm:px-6">Balances</div>
            <button type="button" class="side-arrow w-full border-b border-line bg-soft" data-scroll-up hidden aria-label="Balances above">
                <svg viewBox="0 0 16 16" class="h-4 w-4" aria-hidden="true"><path d="M3.5 10.5 8 6l4.5 4.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path></svg>
            </button>
            <div class="arrow-scroll min-h-0 flex-1" data-arrow-scroll>
                <table class="w-full text-left text-sm">
                    <thead class="bg-soft">
                        <tr>
                            <th class="px-4 py-3 font-semibold sm:px-6">Account</th>
                            <th class="px-4 py-3 font-semibold sm:px-6">Name</th>
                            <th class="hidden px-4 py-3 font-semibold sm:table-cell sm:px-6">MT5</th>
                            <th class="px-4 py-3 text-right font-semibold sm:px-6">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($accounts as $account)
                            <tr class="border-t border-line">
                                <td class="px-4 py-3 font-semibold tabular-nums sm:px-6">{{ $account->number }}</td>
                                <td class="px-4 py-3 sm:px-6">{{ $account->user->name }}</td>
                                <td class="hidden px-4 py-3 sm:table-cell sm:px-6">{{ $account->mt5Account?->name ?: '—' }}</td>
                                <td class="px-4 py-3 text-right tabular-nums sm:px-6">{{ number_format((float) $account->balance, 2, '.', ',') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-8 text-ink/70 sm:px-6" colspan="4">No client accounts yet. Open one under Client accounts first.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <button type="button" class="side-arrow w-full border-t border-line bg-soft" data-scroll-down hidden aria-label="Balances below">
                <svg viewBox="0 0 16 16" class="h-4 w-4" aria-hidden="true"><path d="M3.5 5.5 8 10l4.5-4.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path></svg>
            </button>
        </div>

        <div class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-2xl border border-line bg-card" data-arrow-pane>
            <div class="shrink-0 border-b border-line bg-soft px-4 py-2 text-sm font-semibold sm:px-6">Recent entries</div>
            <button type="button" class="side-arrow w-full border-b border-line bg-soft" data-scroll-up hidden aria-label="Entries above">
                <svg viewBox="0 0 16 16" class="h-4 w-4" aria-hidden="true"><path d="M3.5 10.5 8 6l4.5 4.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path></svg>
            </button>
            <div class="arrow-scroll min-h-0 flex-1" data-arrow-scroll>
                <table class="w-full text-left text-sm">
                    <thead class="sticky top-0 bg-soft">
                        <tr>
                            <th class="px-4 py-3 font-semibold sm:px-6">When</th>
                            <th class="px-4 py-3 font-semibold sm:px-6">Account</th>
                            <th class="px-4 py-3 font-semibold sm:px-6">Type</th>
                            <th class="px-4 py-3 text-right font-semibold sm:px-6">Amount</th>
                            <th class="hidden px-4 py-3 text-right font-semibold sm:table-cell sm:px-6">Balance after</th>
                            <th class="hidden px-4 py-3 font-semibold md:table-cell sm:px-6">Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($entries as $entry)
                            <tr class="border-t border-line">
                                <td class="px-4 py-3 tabular-nums sm:px-6">{{ $entry->created_at->timezone(config('app.timezone'))->format('d M H:i') }}</td>
                                <td class="px-4 py-3 sm:px-6">
                                    <span class="font-semibold tabular-nums">{{ $entry->clientAccount->number }}</span>
                                    <span class="text-ink/70"> {{ $entry->clientAccount->user->name }}</span>
                                </td>
                                <td class="px-4 py-3 sm:px-6">{{ $entry->label() }}</td>
                                <td class="px-4 py-3 text-right tabular-nums sm:px-6 {{ $entry->amount < 0 ? 'text-red-700' : '' }}">
                                    {{ number_format((float) $entry->amount, 2, '.', ',') }}
                                </td>
                                <td class="hidden px-4 py-3 text-right tabular-nums sm:table-cell sm:px-6">{{ number_format((float) $entry->balance_after, 2, '.', ',') }}</td>
                                <td class="hidden px-4 py-3 md:table-cell sm:px-6">{{ $entry->note ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-8 text-ink/70 sm:px-6" colspan="6">No ledger entries yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <button type="button" class="side-arrow w-full border-t border-line bg-soft" data-scroll-down hidden aria-label="Entries below">
                <svg viewBox="0 0 16 16" class="h-4 w-4" aria-hidden="true"><path d="M3.5 5.5 8 10l4.5-4.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path></svg>
            </button>
        </div>
    </div>

    @push('windows')
        <x-desk-window id="ledger-window" title="Post ledger entry" :tab="route('forex.ledger.create')" :open="$errors->any() && old('_form') === 'ledger'">
            @include('forex.ledger.partials.form', ['accounts' => $accounts])
        </x-desk-window>
    @endpush
</x-layouts.shell>
