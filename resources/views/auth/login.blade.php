<x-layouts.app title="Log in">
    <p class="text-sm tracking-wide text-ink/70">Desk</p>
    <h1 class="mt-3 text-4xl font-semibold">Log in</h1>
    <p class="mt-4 text-lg leading-8">Sign in and Home opens.</p>

    <form method="POST" action="{{ route('enter') }}" class="mt-10">
        @csrf
        <button type="submit" class="rounded-xl bg-primary px-5 py-3 font-semibold">Open dashboard</button>
    </form>

    <form method="POST" action="{{ route('login') }}" class="mt-6 rounded-2xl border border-line bg-card p-6">
        @csrf

        <label class="block text-sm" for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email', 'admin@gmail.com') }}" required autofocus autocomplete="username" class="mt-2 w-full rounded-xl border border-line bg-canvas px-4 py-3">
        @error('email')
            <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
        @enderror

        <label class="mt-5 block text-sm" for="password">Password</label>
        <input id="password" name="password" type="text" value="admin" required autocomplete="current-password" class="mt-2 w-full rounded-xl border border-line bg-canvas px-4 py-3">

        <label class="mt-5 flex items-center gap-2 text-sm">
            <input type="checkbox" name="remember" value="1">
            Remember me
        </label>

        <button type="submit" class="mt-6 rounded-xl bg-primary px-5 py-3 font-semibold">Sign in</button>
    </form>
</x-layouts.app>
