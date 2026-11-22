<x-layouts.shell title="Client accounts" :user="$user">
    <x-slot:panel>
        <button type="button" data-window-open="client-account-window" class="rounded-xl bg-card px-4 py-2 text-sm font-semibold">Open account</button>
    </x-slot:panel>

    <div class="overflow-hidden rounded-2xl border border-line bg-card">
        <table class="w-full text-left text-sm">
            <thead class="bg-soft">
                <tr>
                    <th class="px-4 py-3 font-semibold sm:px-6">Account number</th>
                    <th class="px-4 py-3 font-semibold sm:px-6">Name</th>
                    <th class="hidden px-4 py-3 font-semibold md:table-cell sm:px-6">Email</th>
                    <th class="px-4 py-3 text-right font-semibold sm:px-6">Balance</th>
                    <th class="px-4 py-3 font-semibold sm:px-6">MT5 account</th>
                    <th class="px-4 py-3 font-semibold sm:px-6">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($accounts as $account)
                    <tr class="border-t border-line">
                        <td class="px-4 py-4 font-semibold tabular-nums sm:px-6">{{ $account->number }}</td>
                        <td class="px-4 py-4 sm:px-6">{{ $account->user->name }}</td>
                        <td class="hidden px-4 py-4 md:table-cell sm:px-6">{{ $account->user->email }}</td>
                        <td class="px-4 py-4 text-right tabular-nums sm:px-6">{{ number_format((float) $account->balance, 2, '.', ',') }}</td>
                        <td class="px-4 py-4 sm:px-6">
                            <form method="POST" action="{{ route('forex.client-accounts.update', $account) }}" class="flex flex-wrap items-center gap-2">
                                @csrf
                                @method('PATCH')
                                <select name="mt5_account_id" class="rounded-xl border border-line bg-canvas px-3 py-2 text-sm outline-none focus:border-ink">
                                    <option value="">Not linked</option>
                                    @foreach ($mt5Accounts as $mt5)
                                        <option value="{{ $mt5->id }}" @selected((string) $account->mt5_account_id === (string) $mt5->id)>{{ $mt5->name }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="rounded-xl border border-line bg-canvas px-3 py-2 text-sm font-semibold">Save</button>
                            </form>
                        </td>
                        <td class="px-4 py-4 sm:px-6">{{ $account->active ? 'On' : 'Off' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-10 text-ink/70 sm:px-6" colspan="6">No client accounts yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @push('windows')
        <x-desk-window id="client-account-window" title="Open account" :tab="route('forex.client-accounts.create')" :open="$errors->any() && old('_form') === 'client'">
            @include('forex.client-accounts.partials.form')
        </x-desk-window>
    @endpush
</x-layouts.shell>
