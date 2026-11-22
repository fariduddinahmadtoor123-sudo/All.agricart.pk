<form method="POST" action="{{ route('forex.ledger.store') }}" class="flex h-full flex-col gap-4 p-4 sm:gap-6 sm:p-6">
    @csrf
    <input type="hidden" name="_form" value="ledger">
    <p class="text-sm text-ink/70">Money on the client account ledger — separate from the shared MT5 pool.</p>
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
            <option value="adjustment" @selected(old('type') === 'adjustment')>Adjustment</option>
            <option value="trade" @selected(old('type') === 'trade')>Trade</option>
        </select>
        @error('type')
            <span class="text-sm font-normal">{{ $message }}</span>
        @enderror
    </label>
    <label class="flex flex-col gap-2 text-sm font-semibold">
        Amount
        <input name="amount" type="number" step="0.01" value="{{ old('amount') }}" required autocomplete="off" placeholder="1000.00" class="rounded-xl border border-line bg-canvas px-4 py-3 text-base font-normal outline-none focus:border-ink">
        <span class="text-xs font-normal text-ink/60">Deposit and withdrawal use a positive amount. Adjustment and trade may be negative.</span>
        @error('amount')
            <span class="text-sm font-normal">{{ $message }}</span>
        @enderror
    </label>
    <label class="flex flex-col gap-2 text-sm font-semibold">
        Note
        <input name="note" type="text" value="{{ old('note') }}" autocomplete="off" class="rounded-xl border border-line bg-canvas px-4 py-3 text-base font-normal outline-none focus:border-ink">
        @error('note')
            <span class="text-sm font-normal">{{ $message }}</span>
        @enderror
    </label>
    <div class="mt-auto flex justify-end gap-2 border-t border-line pt-4">
        @isset($cancel)
            <a href="{{ $cancel }}" class="rounded-xl border border-line bg-canvas px-4 py-2 text-sm font-semibold">Cancel</a>
        @else
            <button type="button" data-window-close class="rounded-xl border border-line bg-canvas px-4 py-2 text-sm font-semibold">Cancel</button>
        @endisset
        <button type="submit" class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold">Post entry</button>
    </div>
</form>
