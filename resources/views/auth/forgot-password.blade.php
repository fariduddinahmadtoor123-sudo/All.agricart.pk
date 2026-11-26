<x-layouts.app title="Forgot password">
    <p class="text-sm tracking-wide text-ink/70">Desk</p>
    <h1 class="mt-3 text-4xl font-semibold">Forgot password</h1>
    <p class="mt-4 text-lg leading-8">Enter your email. We will send a reset link.</p>
    @if (session('status'))
        <p class="mt-4 text-sm">{{ session('status') }}</p>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="mt-6 rounded-2xl border border-line bg-card p-6">
        @csrf

        <label class="block text-sm" for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="mt-2 w-full rounded-xl border border-line bg-canvas px-4 py-3">
        @error('email')
            <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
        @enderror

        <button type="submit" class="mt-6 rounded-xl bg-primary px-5 py-3 font-semibold">Send reset link</button>
    </form>

    <p class="mt-6 text-sm">
        <a href="{{ route('login') }}" class="font-semibold underline underline-offset-4">Back to log in</a>
    </p>
</x-layouts.app>
