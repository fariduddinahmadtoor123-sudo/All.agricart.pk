<x-layouts.app title="Log in">
    <p class="text-sm tracking-wide text-ink/70">Desk</p>
    <h1 class="mt-3 text-4xl font-semibold">Log in</h1>
    <p class="mt-4 text-lg leading-8">Sign in and Home opens.</p>
    @if (session('status'))
        <p class="mt-4 text-sm">{{ session('status') }}</p>
    @endif

    <form method="POST" action="{{ route('login') }}" class="mt-6 rounded-2xl border border-line bg-card p-6">
        @csrf

        <label class="block text-sm" for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="mt-2 w-full rounded-xl border border-line bg-canvas px-4 py-3">
        @error('email')
            <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
        @enderror

        <label class="mt-5 block text-sm" for="password">Password</label>
        <div class="relative mt-2">
            <input id="password" name="password" type="password" required autocomplete="current-password" class="w-full rounded-xl border border-line bg-canvas py-3 pr-12 pl-4">
            <x-password-eye input="password" />
        </div>

        <div class="mt-5 flex flex-wrap items-center justify-between gap-3 text-sm">
            <label class="flex items-center gap-2">
                <input type="checkbox" name="remember" value="1">
                Remember me
            </label>
            <a href="{{ route('password.request') }}" class="font-semibold underline underline-offset-4">Forgot password?</a>
        </div>

        <button type="submit" class="mt-6 rounded-xl bg-primary px-5 py-3 font-semibold">Sign in</button>
    </form>

    @include('auth.partials.password-toggle')
</x-layouts.app>
