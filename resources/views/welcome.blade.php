<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Desk') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-canvas font-sans text-ink antialiased">
        <main class="mx-auto flex min-h-screen w-full max-w-3xl flex-col justify-center px-6 py-16">
            <p class="text-sm tracking-wide text-ink/70">New system</p>
            <h1 class="mt-3 text-4xl font-semibold">Desk</h1>
            <p class="mt-4 max-w-xl text-lg leading-8">
                One system, in these five colors. Trading, the store, and business will live here.
            </p>
            <p class="mt-8">
                @auth
                    <a href="{{ route('dashboard') }}" class="inline-block rounded-xl bg-primary px-5 py-3 font-semibold">Home</a>
                @else
                    <a href="{{ route('login') }}" class="inline-block rounded-xl bg-primary px-5 py-3 font-semibold">Log in</a>
                @endauth
            </p>

            <ul class="mt-10 grid grid-cols-2 gap-3 sm:grid-cols-5">
                @foreach (['#F5EBE0', '#EDEDE9', '#E3D5CA', '#D6CCC2', '#D5BDAF'] as $color)
                    <li class="overflow-hidden rounded-2xl border border-line">
                        <div class="h-20" style="background-color: {{ $color }}"></div>
                        <p class="bg-card px-3 py-2 text-xs tracking-wide">{{ $color }}</p>
                    </li>
                @endforeach
            </ul>

            <section class="mt-10 grid gap-3 sm:grid-cols-3">
                @foreach (['Trading', 'Store', 'Business'] as $part)
                    <article class="rounded-2xl border border-line bg-card px-5 py-6">
                        <h2 class="text-lg font-semibold">{{ $part }}</h2>
                    </article>
                @endforeach
            </section>
        </main>
    </body>
</html>
