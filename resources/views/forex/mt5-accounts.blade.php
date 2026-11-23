<x-layouts.shell title="MT5 accounts" :user="$user">
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
            <p class="font-semibold">Connector ready on this PC (not a download).</p>
            <p class="mt-1 break-all text-ink/80">{{ session('connector_ready') }}</p>
            <p class="mt-2 text-ink/70">Prefer the <span class="font-semibold">Start connector</span> button. Or open that folder and run <span class="font-semibold">start-mt5.bat</span>.</p>
        </div>
    @endif

    <div class="overflow-hidden rounded-2xl border border-line bg-card">
        <table class="w-full text-left text-sm">
            <thead class="bg-soft">
                <tr>
                    <th class="px-4 py-3 font-semibold sm:px-6">Name</th>
                    <th class="px-4 py-3 font-semibold sm:px-6">Broker</th>
                    <th class="hidden px-4 py-3 font-semibold sm:table-cell sm:px-6">Company</th>
                    <th class="hidden px-4 py-3 font-semibold md:table-cell sm:px-6">Server</th>
                    <th class="px-4 py-3 font-semibold sm:px-6">Login</th>
                    <th class="px-4 py-3 font-semibold sm:px-6">Connector</th>
                    <th class="px-4 py-3 font-semibold sm:px-6">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($accounts as $account)
                    <tr class="border-t border-line">
                        <td class="px-4 py-4 font-semibold sm:px-6">{{ $account->name }}</td>
                        <td class="px-4 py-4 sm:px-6">{{ $account->broker }}</td>
                        <td class="hidden px-4 py-4 sm:table-cell sm:px-6">{{ $account->company ?: '—' }}</td>
                        <td class="hidden px-4 py-4 md:table-cell sm:px-6">{{ $account->server }}</td>
                        <td class="px-4 py-4 tabular-nums sm:px-6">{{ $account->login }}</td>
                        <td class="px-4 py-4 sm:px-6">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-xs font-semibold {{ $account->connectorIsOnline() ? 'text-ink' : 'text-ink/50' }}">
                                    {{ $account->connectorIsOnline() ? 'Online' : 'Offline' }}
                                </span>
                                <form method="POST" action="{{ route('forex.mt5-accounts.connector.prepare', $account) }}">
                                    @csrf
                                    <button type="submit" class="rounded-xl bg-primary px-3 py-2 text-sm font-semibold">Prepare on this PC</button>
                                </form>
                                <form method="POST" action="{{ route('forex.mt5-accounts.connector.start', $account) }}">
                                    @csrf
                                    <button type="submit" class="rounded-xl border border-line bg-canvas px-3 py-2 text-sm font-semibold">Start connector</button>
                                </form>
                                <form method="POST" action="{{ route('forex.mt5-accounts.destroy', $account) }}" onsubmit="return confirm('Delete this MT5 account?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-xl border border-line bg-canvas px-3 py-2 text-sm font-semibold">Delete</button>
                                </form>
                            </div>
                        </td>
                        <td class="px-4 py-4 sm:px-6">{{ $account->active ? 'On' : 'Off' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-10 text-ink/70 sm:px-6" colspan="7">No MT5 accounts yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @push('windows')
        <x-desk-window id="mt5-account-window" title="New MT5 account" :tab="route('forex.mt5-accounts.create')" :open="$errors->any() && old('_form') === 'mt5'">
            @include('forex.mt5-accounts.partials.form')
        </x-desk-window>
    @endpush
</x-layouts.shell>
