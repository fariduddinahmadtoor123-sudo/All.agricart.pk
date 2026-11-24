<form method="POST" action="{{ route('forex.payments.store') }}" class="flex h-full flex-col gap-4 p-4 sm:gap-6 sm:p-6">
    @csrf
    <input type="hidden" name="_form" value="payment">
    <p class="text-sm text-ink/70">File the customer’s request. The date and time are saved automatically. Money moves only when you approve it.</p>
    <label class="flex flex-col gap-2 text-sm font-semibold">
        Client account
        <select name="client_account_id" required class="rounded-xl border border-line bg-canvas px-4 py-3 text-base font-normal outline-none focus:border-ink" @disabled($accounts->isEmpty())>
            <option value="">{{ $accounts->isEmpty() ? 'Open a client account first' : 'Select account' }}</option>
            @foreach ($accounts as $account)
                <option value="{{ $account->id }}" @selected((string) old('client_account_id') === (string) $account->id)>
                    {{ $account->number }} — {{ $account->user->name }} ({{ number_format((float) $account->balance, 2, '.', ',') }})
                </option>
            @endforeach
        </select>
        @error('client_account_id')
            <span class="text-sm font-normal">{{ $message }}</span>
        @enderror
    </label>
    <label class="flex flex-col gap-2 text-sm font-semibold">
        Type
        <select name="type" required class="rounded-xl border border-line bg-canvas px-4 py-3 text-base font-normal outline-none focus:border-ink">
            <option value="deposit" @selected(old('type', 'deposit') === 'deposit')>Deposit</option>
            <option value="withdrawal" @selected(old('type') === 'withdrawal')>Withdrawal</option>
        </select>
    </label>
    <label class="flex flex-col gap-2 text-sm font-semibold">
        Amount
        <input name="amount" type="number" min="0.01" step="0.01" value="{{ old('amount') }}" required autocomplete="off" placeholder="100.00" class="rounded-xl border border-line bg-canvas px-4 py-3 text-base font-normal outline-none focus:border-ink">
        @error('amount')
            <span class="text-sm font-normal">{{ $message }}</span>
        @enderror
    </label>
    <label class="flex flex-col gap-2 text-sm font-semibold">
        Reference
        <input name="reference" type="text" value="{{ old('reference') }}" required maxlength="255" autocomplete="off" placeholder="Transfer or receipt number" class="rounded-xl border border-line bg-canvas px-4 py-3 text-base font-normal outline-none focus:border-ink">
        @error('reference')
            <span class="text-sm font-normal">{{ $message }}</span>
        @enderror
    </label>
    <div class="mt-auto flex justify-end gap-2 border-t border-line pt-4">
        @isset($cancel)
            <a href="{{ $cancel }}" class="rounded-xl border border-line bg-canvas px-4 py-2 text-sm font-semibold">Cancel</a>
        @else
            <button type="button" data-window-close class="rounded-xl border border-line bg-canvas px-4 py-2 text-sm font-semibold">Cancel</button>
        @endisset
        <button type="submit" class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold">Add request</button>
    </div>
</form>
