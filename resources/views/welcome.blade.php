<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Desk') }}</title>
        <x-corner-boot />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="flex min-h-screen flex-col bg-canvas font-sans text-ink antialiased">
        <header class="border-b border-line bg-card">
            <div class="mx-auto flex h-16 w-full max-w-6xl items-center justify-between px-6">
                <a href="{{ url('/') }}" class="text-lg font-semibold tracking-wide">Desk</a>
                <div class="flex items-center gap-3">
                    @guest
                        <a href="{{ route('login') }}" class="rounded-xl border border-line bg-canvas px-4 py-2 text-sm font-semibold">Log in</a>
                    @endguest
                    <form method="POST" action="{{ route('enter') }}">
                        @csrf
                        <button type="submit" class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold">Dashboard</button>
                    </form>
                </div>
            </div>
        </header>

        <main class="mx-auto flex w-full max-w-3xl flex-1 items-center px-6 py-16">
            <p class="text-3xl font-semibold leading-10">The online store will appear here, inshallah.</p>
        </main>

        <footer class="border-t border-line bg-card">
            <div class="mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-5 text-sm text-ink/70">
                <p>Desk</p>
                <p>Online store</p>
            </div>
        </footer>
    </body>
</html>
