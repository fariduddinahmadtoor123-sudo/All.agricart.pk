<form method="POST" action="{{ route('forex.mt5-accounts.store') }}" class="flex h-full flex-col gap-4 p-4 sm:gap-6 sm:p-6">
    @csrf
    <input type="hidden" name="_form" value="mt5">
    <label class="flex flex-col gap-2 text-sm font-semibold">
        Name
        <input name="name" type="text" value="{{ old('name') }}" required autocomplete="off" placeholder="Our label for this pool" class="rounded-xl border border-line bg-canvas px-4 py-3 text-base font-normal outline-none focus:border-ink">
        @error('name')
            <span class="text-sm font-normal">{{ $message }}</span>
        @enderror
    </label>
    <label class="flex flex-col gap-2 text-sm font-semibold">
        Broker
        <input name="broker" type="text" value="{{ old('broker') }}" required autocomplete="off" placeholder="Broker name" class="rounded-xl border border-line bg-canvas px-4 py-3 text-base font-normal outline-none focus:border-ink">
        @error('broker')
            <span class="text-sm font-normal">{{ $message }}</span>
        @enderror
    </label>
    <label class="flex flex-col gap-2 text-sm font-semibold">
        Company
        <input name="company" type="text" value="{{ old('company') }}" autocomplete="off" placeholder="Optional company under that broker" class="rounded-xl border border-line bg-canvas px-4 py-3 text-base font-normal outline-none focus:border-ink">
        @error('company')
            <span class="text-sm font-normal">{{ $message }}</span>
        @enderror
    </label>
    <label class="flex flex-col gap-2 text-sm font-semibold">
        Server
        <input name="server" type="text" value="{{ old('server') }}" required autocomplete="off" placeholder="Trade server name" class="rounded-xl border border-line bg-canvas px-4 py-3 text-base font-normal outline-none focus:border-ink">
        @error('server')
            <span class="text-sm font-normal">{{ $message }}</span>
        @enderror
    </label>
    <label class="flex flex-col gap-2 text-sm font-semibold">
        Login
        <input name="login" type="text" value="{{ old('login') }}" required autocomplete="off" inputmode="numeric" placeholder="MT5 account number" class="rounded-xl border border-line bg-canvas px-4 py-3 text-base font-normal outline-none focus:border-ink">
        @error('login')
            <span class="text-sm font-normal">{{ $message }}</span>
        @enderror
    </label>
    <div class="mt-auto flex justify-end gap-2 border-t border-line pt-4">
        @isset($cancel)
            <a href="{{ $cancel }}" class="rounded-xl border border-line bg-canvas px-4 py-2 text-sm font-semibold">Cancel</a>
        @else
            <button type="button" data-window-close class="rounded-xl border border-line bg-canvas px-4 py-2 text-sm font-semibold">Cancel</button>
        @endisset
        <button type="submit" class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold">Save</button>
    </div>
</form>
