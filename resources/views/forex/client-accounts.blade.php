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
                    <th class="px-4 py-3 font-semibold sm:px-6">Leverage</th>
                    <th class="px-4 py-3 font-semibold sm:px-6">MT5 account</th>
                    <th class="px-4 py-3 text-right font-semibold sm:px-6">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($accounts as $account)
                    <tr class="border-t border-line">
                        <td class="px-4 py-4 font-semibold tabular-nums sm:px-6">{{ $account->number }}</td>
                        <td class="px-4 py-4 sm:px-6">{{ $account->user->name }}</td>
                        <td class="hidden px-4 py-4 md:table-cell sm:px-6">{{ $account->user->email }}</td>
                        <td class="px-4 py-4 text-right tabular-nums sm:px-6">{{ number_format((float) $account->balance, 2, '.', ',') }}</td>
                        <td class="px-4 py-4 tabular-nums sm:px-6">1:{{ $account->leverage }}</td>
                        <td class="px-4 py-4 sm:px-6">{{ $account->mt5Account?->name ?: 'Not linked' }}</td>
                        <td class="px-4 py-4 sm:px-6">
                            <div class="flex flex-wrap justify-end gap-2">
                                <button type="button" data-window-open="client-edit-{{ $account->id }}" class="rounded-xl border border-line bg-canvas px-3 py-2 text-sm font-semibold">Edit</button>
                                <button type="button" data-window-open="client-pay-{{ $account->id }}" class="rounded-xl border border-line bg-canvas px-3 py-2 text-sm font-semibold">Add payment</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-10 text-ink/70 sm:px-6" colspan="7">No client accounts yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @push('windows')
        <x-desk-window id="client-account-window" title="Open account" :tab="route('forex.client-accounts.create')" :open="$errors->any() && old('_form') === 'client'">
            @include('forex.client-accounts.partials.form')
        </x-desk-window>
        @foreach ($accounts as $account)
            <x-desk-window id="client-edit-{{ $account->id }}" title="Edit {{ $account->number }}" :open="$errors->any() && old('_form') === 'edit-'.$account->id">
                <form method="POST" action="{{ route('forex.client-accounts.update', $account) }}" class="flex h-full flex-col gap-4 p-4 sm:gap-6 sm:p-6">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="_form" value="edit-{{ $account->id }}">
                    <label class="flex flex-col gap-2 text-sm font-semibold">
                        Leverage
                        <input name="leverage" type="number" min="1" max="2000" step="1" value="{{ old('_form') === 'edit-'.$account->id ? old('leverage', $account->leverage) : $account->leverage }}" required class="rounded-xl border border-line bg-canvas px-4 py-3 text-base font-normal outline-none focus:border-ink">
                        @error('leverage')
                            <span class="text-sm font-normal">{{ $message }}</span>
                        @enderror
                    </label>
                    <label class="flex flex-col gap-2 text-sm font-semibold">
                        MT5 account
                        <select name="mt5_account_id" class="rounded-xl border border-line bg-canvas px-4 py-3 text-base font-normal outline-none focus:border-ink">
                            <option value="">Not linked</option>
                            @foreach ($mt5Accounts as $mt5)
                                <option value="{{ $mt5->id }}" @selected((string) (old('_form') === 'edit-'.$account->id ? old('mt5_account_id', $account->mt5_account_id) : $account->mt5_account_id) === (string) $mt5->id)>{{ $mt5->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <div class="mt-auto flex justify-end gap-2 border-t border-line pt-4">
                        <button type="button" data-window-close class="rounded-xl border border-line bg-canvas px-4 py-2 text-sm font-semibold">Cancel</button>
                        <button type="submit" class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold">Save</button>
                    </div>
                </form>
            </x-desk-window>
            <x-desk-window id="client-pay-{{ $account->id }}" title="Add payment {{ $account->number }}" :open="$errors->any() && old('_form') === 'pay-'.$account->id">
                <form method="POST" action="{{ route('forex.client-accounts.payment', $account) }}" class="flex h-full flex-col gap-4 p-4 sm:gap-6 sm:p-6">
                    @csrf
                    <input type="hidden" name="_form" value="pay-{{ $account->id }}">
                    <p class="text-sm text-ink/70">Current balance {{ number_format((float) $account->balance, 2, '.', ',') }}. This adds to it. Use it again when the payment is finished.</p>
                    <label class="flex flex-col gap-2 text-sm font-semibold">
                        Amount
                        <input name="amount" type="number" min="0.01" step="0.01" value="{{ old('_form') === 'pay-'.$account->id ? old('amount') : '' }}" required class="rounded-xl border border-line bg-canvas px-4 py-3 text-base font-normal outline-none focus:border-ink">
                        @error('amount')
                            <span class="text-sm font-normal">{{ $message }}</span>
                        @enderror
                    </label>
                    <div class="mt-auto flex justify-end gap-2 border-t border-line pt-4">
                        <button type="button" data-window-close class="rounded-xl border border-line bg-canvas px-4 py-2 text-sm font-semibold">Cancel</button>
                        <button type="submit" class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold">Add payment</button>
                    </div>
                </form>
            </x-desk-window>
        @endforeach
    @endpush
</x-layouts.shell>
