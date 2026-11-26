<x-layouts.app title="Reset password">
    <p class="text-sm tracking-wide text-ink/70">Desk</p>
    <h1 class="mt-3 text-4xl font-semibold">Reset password</h1>
    <p class="mt-4 text-lg leading-8">Choose a new password for your Desk login.</p>

    <form method="POST" action="{{ route('password.update') }}" class="mt-6 rounded-2xl border border-line bg-card p-6">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <label class="block text-sm" for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required autofocus autocomplete="username" class="mt-2 w-full rounded-xl border border-line bg-canvas px-4 py-3">
        @error('email')
            <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
        @enderror

        <label class="mt-5 block text-sm" for="password">New password</label>
        <div class="relative mt-2">
            <input id="password" name="password" type="password" required autocomplete="new-password" class="w-full rounded-xl border border-line bg-canvas py-3 pr-12 pl-4">
            <x-password-eye input="password" />
        </div>
        @error('password')
            <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
        @enderror

        <label class="mt-5 block text-sm" for="password_confirmation">Confirm password</label>
        <div class="relative mt-2">
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="w-full rounded-xl border border-line bg-canvas py-3 pr-12 pl-4">
            <x-password-eye input="password_confirmation" />
        </div>

        <button type="submit" class="mt-6 rounded-xl bg-primary px-5 py-3 font-semibold">Save new password</button>
    </form>

    @include('auth.partials.password-toggle')
</x-layouts.app>
