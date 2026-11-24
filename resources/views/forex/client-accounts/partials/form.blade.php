<form method="POST" action="{{ route('forex.client-accounts.store') }}" class="flex h-full flex-col gap-4 p-4 sm:gap-6 sm:p-6">
    @csrf
    <input type="hidden" name="_form" value="client">
    <p class="text-sm text-ink/70">Pick an existing user. The account number is created automatically.</p>
    <label class="flex flex-col gap-2 text-sm font-semibold">
        User
        <select name="user_id" required class="rounded-xl border border-line bg-canvas px-4 py-3 text-base font-normal outline-none focus:border-ink" @disabled($users->isEmpty())>
            <option value="">{{ $users->isEmpty() ? 'No users left without an account' : 'Select user' }}</option>
            @foreach ($users as $member)
                <option value="{{ $member->id }}" @selected((string) old('user_id') === (string) $member->id)>
                    {{ $member->name }} — {{ $member->email }}
                </option>
            @endforeach
        </select>
        @error('user_id')
            <span class="text-sm font-normal">{{ $message }}</span>
        @enderror
    </label>
    <label class="flex flex-col gap-2 text-sm font-semibold">
        MT5 account
        <select name="mt5_account_id" class="rounded-xl border border-line bg-canvas px-4 py-3 text-base font-normal outline-none focus:border-ink">
            <option value="">Assign later</option>
            @foreach ($mt5Accounts as $mt5)
                <option value="{{ $mt5->id }}" @selected((string) old('mt5_account_id') === (string) $mt5->id)>{{ $mt5->name }}</option>
            @endforeach
        </select>
        @error('mt5_account_id')
            <span class="text-sm font-normal">{{ $message }}</span>
        @enderror
    </label>
    <label class="flex flex-col gap-2 text-sm font-semibold">
        Leverage
        <input name="leverage" type="number" min="1" max="2000" step="1" value="{{ old('leverage', 100) }}" required class="rounded-xl border border-line bg-canvas px-4 py-3 text-base font-normal outline-none focus:border-ink">
        @error('leverage')
            <span class="text-sm font-normal">{{ $message }}</span>
        @enderror
    </label>
    <label class="flex flex-col gap-2 text-sm font-semibold">
        Payment
        <input name="amount" type="number" min="0" step="0.01" value="{{ old('amount', '10000') }}" required class="rounded-xl border border-line bg-canvas px-4 py-3 text-base font-normal outline-none focus:border-ink">
        <span class="text-xs font-normal text-ink/60">Opening payment. More payments can be added on the account later. Use 0 when there is no payment yet.</span>
        @error('amount')
            <span class="text-sm font-normal">{{ $message }}</span>
        @enderror
    </label>
    <div class="mt-auto flex justify-end gap-2 border-t border-line pt-4">
        @isset($cancel)
            <a href="{{ $cancel }}" class="rounded-xl border border-line bg-canvas px-4 py-2 text-sm font-semibold">Cancel</a>
        @else
            <button type="button" data-window-close class="rounded-xl border border-line bg-canvas px-4 py-2 text-sm font-semibold">Cancel</button>
        @endisset
        <button type="submit" class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold">Open account</button>
    </div>
</form>
