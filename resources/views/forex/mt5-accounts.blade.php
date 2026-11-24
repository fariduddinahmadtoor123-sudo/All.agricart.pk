<x-layouts.shell title="MT5 accounts" :user="$user" fit>
    <x-slot:panel>
        <button type="button" data-window-open="mt5-account-window" class="rounded-xl bg-card px-4 py-2 text-sm font-semibold">Add MT5 account</button>
    </x-slot:panel>

    @if ($errors->has('connector'))
        <div class="mb-4 rounded-2xl border border-line bg-card px-4 py-3 text-sm sm:px-6">
            <p class="font-semibold">Connector could not start.</p>
            <p class="mt-1 text-ink/80">{{ $errors->first('connector') }}</p>
        </div>
    @endif

    @if (session('connector_started'))
        <div class="mb-4 rounded-2xl border border-line bg-card px-4 py-3 text-sm sm:px-6">
            <p class="font-semibold">Connector window started.</p>
            <p class="mt-1 text-ink/70">Leave the black window open. Sign in to the matching MT5 login first. Desk will show Online when rates arrive.</p>
        </div>
    @endif

    @if (session('connector_ready'))
        <div class="mb-4 rounded-2xl border border-line bg-card px-4 py-3 text-sm sm:px-6">
            <p class="font-semibold">Connector files are on the server.</p>
            <p class="mt-1 text-ink/70">Use <span class="font-semibold">Download connector</span>, unzip it on your PC, and run <span class="font-semibold">start-mt5.bat</span>. Leave the black window open.</p>
        </div>
    @endif

    <div class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-2xl border border-line bg-card" data-arrow-pane>
        <button type="button" class="side-arrow w-full border-b border-line bg-soft" data-scroll-up hidden aria-label="Accounts above">
            <svg viewBox="0 0 16 16" class="h-4 w-4" aria-hidden="true"><path d="M3.5 10.5 8 6l4.5 4.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path></svg>
        </button>
        <div class="arrow-scroll min-h-0 flex-1" data-arrow-scroll>
            <table class="w-full text-left text-sm">
                <thead class="sticky top-0 bg-soft">
                    <tr>
                        <th class="px-4 py-3 font-semibold sm:px-6">Name</th>
                        <th class="px-4 py-3 font-semibold sm:px-6">Login</th>
                        <th class="px-4 py-3 text-right font-semibold sm:px-6">Broker balance</th>
                        <th class="px-4 py-3 text-right font-semibold sm:px-6">Partner cut</th>
                        <th class="hidden px-4 py-3 text-right font-semibold md:table-cell sm:px-6">Customer cut</th>
                        <th class="px-4 py-3 font-semibold sm:px-6">Connector</th>
                        <th class="px-4 py-3 font-semibold sm:px-6">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($accounts as $account)
                        @php($cut = $cuts->get($account->id))
                        <tr class="border-t border-line">
                            <td class="px-4 py-4 sm:px-6">
                                <p class="font-semibold">{{ $account->name }}</p>
                                <p class="text-ink/70">{{ $account->broker }}{{ $account->company ? ' · '.$account->company : '' }}</p>
                                <p class="text-xs text-ink/60">Partner {{ number_format((float) $account->partner_rate, 2, '.', ',') }} / lot · Customer {{ number_format((float) $account->customer_rate, 2, '.', ',') }} / lot</p>
                            </td>
                            <td class="px-4 py-4 tabular-nums sm:px-6">{{ $account->login }}</td>
                            <td class="px-4 py-4 text-right font-semibold tabular-nums sm:px-6">{{ $account->mt5_balance === null ? '—' : number_format((float) $account->mt5_balance, 2, '.', ',') }}</td>
                            <td class="px-4 py-4 text-right tabular-nums sm:px-6">{{ number_format((float) ($cut->partner_cut ?? 0), 2, '.', ',') }}</td>
                            <td class="hidden px-4 py-4 text-right tabular-nums md:table-cell sm:px-6">{{ number_format((float) ($cut->customer_cut ?? 0), 2, '.', ',') }}</td>
                            <td class="px-4 py-4 sm:px-6">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-xs font-semibold {{ $account->connectorIsOnline() ? 'text-ink' : 'text-ink/50' }}">
                                        {{ $account->connectorIsOnline() ? 'Online' : 'Offline' }}
                                    </span>
                                    <a href="{{ route('forex.mt5-accounts.connector', $account) }}" class="rounded-xl bg-primary px-3 py-2 text-sm font-semibold">Download connector</a>
                                    <form method="POST" action="{{ route('forex.mt5-accounts.connector.prepare', $account) }}">
                                        @csrf
                                        <button type="submit" class="rounded-xl bg-primary px-3 py-2 text-sm font-semibold">Prepare on this PC</button>
                                    </form>
                                    <form method="POST" action="{{ route('forex.mt5-accounts.connector.start', $account) }}">
                                        @csrf
                                        <button type="submit" class="rounded-xl border border-line bg-canvas px-3 py-2 text-sm font-semibold">Start connector</button>
                                    </form>
                                    <button
                                        type="button"
                                        data-window-open="rates-window"
                                        data-rates-url="{{ route('forex.mt5-accounts.rates', $account) }}"
                                        data-rates-who="{{ $account->name }}"
                                        data-partner="{{ number_format((float) $account->partner_rate, 2, '.', '') }}"
                                        data-customer="{{ number_format((float) $account->customer_rate, 2, '.', '') }}"
                                        class="rounded-xl border border-line bg-canvas px-3 py-2 text-sm font-semibold"
                                    >Rates</button>
                                </div>
                            </td>
                            <td class="px-4 py-4 sm:px-6">
                                @if ($account->active)
                                    <button
                                        type="button"
                                        data-window-open="deactivate-window"
                                        data-deactivate-url="{{ route('forex.mt5-accounts.deactivate', $account) }}"
                                        data-deactivate-who="{{ $account->name }}"
                                        class="rounded-xl border border-line bg-canvas px-3 py-2 text-sm font-semibold"
                                    >Deactivate</button>
                                @else
                                    <p class="mb-2 text-sm">{{ $account->reasonLabel() ?: 'Off' }}</p>
                                    <form method="POST" action="{{ route('forex.mt5-accounts.reactivate', $account) }}">
                                        @csrf
                                        <button type="submit" class="rounded-xl border border-line bg-canvas px-3 py-2 text-sm font-semibold">Reactivate</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="px-4 py-10 text-ink/70 sm:px-6" colspan="7">No MT5 accounts yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <button type="button" class="side-arrow w-full border-t border-line bg-soft" data-scroll-down hidden aria-label="Accounts below">
            <svg viewBox="0 0 16 16" class="h-4 w-4" aria-hidden="true"><path d="M3.5 5.5 8 10l4.5-4.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path></svg>
        </button>
    </div>

    @push('windows')
        <x-desk-window id="mt5-account-window" title="New MT5 account" :tab="route('forex.mt5-accounts.create')" :open="$errors->any() && old('_form') === 'mt5'">
            @include('forex.mt5-accounts.partials.form')
        </x-desk-window>
        <x-desk-window id="rates-window" title="Rates" :open="$errors->has('partner_rate') || $errors->has('customer_rate')">
            <form id="rates-form" method="POST" action="{{ route('forex.mt5-accounts') }}" class="flex h-full flex-col gap-4 p-4 sm:gap-6 sm:p-6">
                @csrf
                <p id="rates-who" class="text-sm text-ink/70">USD per lot. The customer rate is taken when a trade opens. The partner rate is what the broker takes from this account.</p>
                <label class="flex flex-col gap-2 text-sm font-semibold">
                    Partner rate
                    <input name="partner_rate" type="number" min="0" step="0.01" required class="rounded-xl border border-line bg-canvas px-4 py-3 text-base font-normal outline-none focus:border-ink">
                    @error('partner_rate')
                        <span class="text-sm font-normal">{{ $message }}</span>
                    @enderror
                </label>
                <label class="flex flex-col gap-2 text-sm font-semibold">
                    Customer rate
                    <input name="customer_rate" type="number" min="0" step="0.01" required class="rounded-xl border border-line bg-canvas px-4 py-3 text-base font-normal outline-none focus:border-ink">
                    @error('customer_rate')
                        <span class="text-sm font-normal">{{ $message }}</span>
                    @enderror
                </label>
                <div class="mt-auto flex justify-end gap-2 border-t border-line pt-4">
                    <button type="button" data-window-close class="rounded-xl border border-line bg-canvas px-4 py-2 text-sm font-semibold">Cancel</button>
                    <button type="submit" class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold">Save rates</button>
                </div>
            </form>
        </x-desk-window>
        <x-desk-window id="deactivate-window" title="Deactivate account" :open="$errors->has('reason')">
            <form id="deactivate-form" method="POST" action="{{ route('forex.mt5-accounts') }}" class="flex h-full flex-col gap-4 p-4 sm:gap-6 sm:p-6">
                @csrf
                <p id="deactivate-who" class="text-sm text-ink/70">The account stays on file. Pick why it is being switched off.</p>
                <label class="flex flex-col gap-2 text-sm font-semibold">
                    Reason
                    <select name="reason" required class="rounded-xl border border-line bg-canvas px-4 py-3 text-base font-normal outline-none focus:border-ink">
                        <option value="">Choose a reason</option>
                        @foreach (\Modules\Forex\Models\Mt5Account::REASONS as $value => $label)
                            <option value="{{ $value }}" @selected(old('reason') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('reason')
                        <span class="text-sm font-normal">{{ $message }}</span>
                    @enderror
                </label>
                <div class="mt-auto flex justify-end gap-2 border-t border-line pt-4">
                    <button type="button" data-window-close class="rounded-xl border border-line bg-canvas px-4 py-2 text-sm font-semibold">Cancel</button>
                    <button type="submit" class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold">Deactivate</button>
                </div>
            </form>
        </x-desk-window>
        <script>
            document.querySelectorAll('[data-rates-url]').forEach((button) => {
                button.addEventListener('click', () => {
                    const form = document.getElementById('rates-form');

                    if (! form) {
                        return;
                    }

                    form.action = button.dataset.ratesUrl;
                    form.querySelector('[name="partner_rate"]').value = button.dataset.partner;
                    form.querySelector('[name="customer_rate"]').value = button.dataset.customer;
                    document.getElementById('rates-who').textContent = button.dataset.ratesWho + '. USD per lot. The customer rate is taken when a trade opens. The partner rate is what the broker takes from this account.';
                });
            });

            document.querySelectorAll('[data-deactivate-url]').forEach((button) => {
                button.addEventListener('click', () => {
                    const form = document.getElementById('deactivate-form');

                    if (! form) {
                        return;
                    }

                    form.action = button.dataset.deactivateUrl;
                    document.getElementById('deactivate-who').textContent = button.dataset.deactivateWho + '. The account stays on file. Pick why it is being switched off.';
                });
            });
        </script>
    @endpush
</x-layouts.shell>
