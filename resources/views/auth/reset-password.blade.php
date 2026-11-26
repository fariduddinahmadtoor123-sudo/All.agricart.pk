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
            <input id="password" name="password" type="password" required autocomplete="new-password" class="w-full rounded-xl border border-line bg-canvas py-3 pe-12 ps-4">
            <button type="button" data-password-toggle="password" class="absolute inset-y-0 end-0 grid w-12 place-items-center text-ink/70 hover:text-ink" aria-label="Show password" aria-pressed="false">
                <svg data-eye-open viewBox="0 0 16 16" class="h-4 w-4" aria-hidden="true">
                    <path d="M1.5 8s2.5-4.5 6.5-4.5S14.5 8 14.5 8s-2.5 4.5-6.5 4.5S1.5 8 1.5 8Z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"></path>
                    <circle cx="8" cy="8" r="2" fill="none" stroke="currentColor" stroke-width="1.4"></circle>
                </svg>
                <svg data-eye-shut viewBox="0 0 16 16" class="h-4 w-4" hidden aria-hidden="true">
                    <path d="M2 2.5 14 13.5" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"></path>
                    <path d="M6.2 4.1A7.2 7.2 0 0 1 8 3.5c4 0 6.5 4.5 6.5 4.5a11 11 0 0 1-2.1 2.3M4.2 5.7A11 11 0 0 0 1.5 8S4 12.5 8 12.5c.7 0 1.4-.1 2-.3" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
            </button>
        </div>
        @error('password')
            <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
        @enderror

        <label class="mt-5 block text-sm" for="password_confirmation">Confirm password</label>
        <div class="relative mt-2">
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="w-full rounded-xl border border-line bg-canvas py-3 pe-12 ps-4">
            <button type="button" data-password-toggle="password_confirmation" class="absolute inset-y-0 end-0 grid w-12 place-items-center text-ink/70 hover:text-ink" aria-label="Show password" aria-pressed="false">
                <svg data-eye-open viewBox="0 0 16 16" class="h-4 w-4" aria-hidden="true">
                    <path d="M1.5 8s2.5-4.5 6.5-4.5S14.5 8 14.5 8s-2.5 4.5-6.5 4.5S1.5 8 1.5 8Z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"></path>
                    <circle cx="8" cy="8" r="2" fill="none" stroke="currentColor" stroke-width="1.4"></circle>
                </svg>
                <svg data-eye-shut viewBox="0 0 16 16" class="h-4 w-4" hidden aria-hidden="true">
                    <path d="M2 2.5 14 13.5" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"></path>
                    <path d="M6.2 4.1A7.2 7.2 0 0 1 8 3.5c4 0 6.5 4.5 6.5 4.5a11 11 0 0 1-2.1 2.3M4.2 5.7A11 11 0 0 0 1.5 8S4 12.5 8 12.5c.7 0 1.4-.1 2-.3" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
            </button>
        </div>

        <button type="submit" class="mt-6 rounded-xl bg-primary px-5 py-3 font-semibold">Save new password</button>
    </form>

    @include('auth.partials.password-toggle')
</x-layouts.app>
