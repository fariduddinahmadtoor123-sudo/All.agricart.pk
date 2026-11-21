@props(['title'])

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title }} — {{ config('app.name', 'Desk') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-canvas font-sans text-ink antialiased">
        <main class="mx-auto flex min-h-screen w-full max-w-3xl flex-col justify-center px-6 py-16">
            {{ $slot }}
        </main>
    </body>
</html>
