@props(['title', 'user'])

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title }} — {{ config('app.name', 'Desk') }}</title>
        <script>
            (() => {
                const saved = localStorage.getItem('desk-scheme');

                if (! saved) {
                    return;
                }

                try {
                    const scheme = JSON.parse(saved);
                    const root = document.documentElement;
                    const value = scheme.page.replace('#', '');
                    const color = Number.parseInt(value, 16);
                    const light = (((color >> 16) & 255) * 299 + ((color >> 8) & 255) * 587 + (color & 255) * 114) / 1000;

                    root.style.setProperty('--color-canvas', scheme.page);
                    root.style.setProperty('--color-card', scheme.card);
                    root.style.setProperty('--color-soft', scheme.soft);
                    root.style.setProperty('--color-line', scheme.line);
                    root.style.setProperty('--color-primary', scheme.primary);
                    root.style.setProperty('--color-ink', light > 150 ? '#3a322b' : '#f7f6f2');
                } catch (error) {
                    localStorage.removeItem('desk-scheme');
                }
            })();
        </script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="flex h-screen flex-col bg-canvas font-sans text-ink antialiased">
        <header class="flex h-16 shrink-0 items-center justify-between border-b border-line bg-card px-6">
            <div class="flex items-center gap-4">
                <x-section-tag number="1" name="Top" />
                <p class="text-lg font-semibold tracking-wide">Desk</p>
                @php
                    $crumbs = match (true) {
                        request()->routeIs('roles.create') => [
                            ['label' => 'Settings', 'route' => 'settings'],
                            ['label' => 'Roles', 'route' => 'roles'],
                            ['label' => 'New role'],
                        ],
                        request()->routeIs('roles') => [
                            ['label' => 'Settings', 'route' => 'settings'],
                            ['label' => 'Roles'],
                        ],
                        request()->routeIs('colors.create') => [
                            ['label' => 'Settings', 'route' => 'settings'],
                            ['label' => 'Colors', 'route' => 'colors'],
                            ['label' => 'New color'],
                        ],
                        request()->routeIs('colors.edit') => [
                            ['label' => 'Settings', 'route' => 'settings'],
                            ['label' => 'Colors', 'route' => 'colors'],
                            ['label' => 'Edit color'],
                        ],
                        request()->routeIs('colors') => [
                            ['label' => 'Settings', 'route' => 'settings'],
                            ['label' => 'Colors'],
                        ],
                        request()->routeIs('notes.create') => [
                            ['label' => 'Docs'],
                            ['label' => 'Notes', 'route' => 'notes'],
                            ['label' => 'New note'],
                        ],
                        request()->routeIs('notes.edit') => [
                            ['label' => 'Docs'],
                            ['label' => 'Notes', 'route' => 'notes'],
                            ['label' => 'Edit note'],
                        ],
                        request()->routeIs('notes') => [
                            ['label' => 'Docs'],
                            ['label' => 'Notes'],
                        ],
                        request()->routeIs('settings') => [
                            ['label' => 'Settings'],
                        ],
                        request()->routeIs('forex') => [
                            ['label' => 'Forex'],
                        ],
                        default => [
                            ['label' => 'Home'],
                        ],
                    };
                @endphp
                <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-sm">
                    @foreach ($crumbs as $crumb)
                        <span aria-hidden="true">/</span>
                        @isset($crumb['route'])
                            <a href="{{ route($crumb['route']) }}" class="hover:underline">{{ $crumb['label'] }}</a>
                        @else
                            <span class="font-semibold">{{ $crumb['label'] }}</span>
                        @endisset
                    @endforeach
                </nav>
            </div>
            <div class="flex items-center gap-4">
                <p class="text-sm">{{ $user->name }}</p>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-xl border border-line bg-canvas px-4 py-2 text-sm font-semibold">Log out</button>
                </form>
            </div>
        </header>

        <div class="flex min-h-0 flex-1">
            <nav id="side-menu" class="side-menu flex shrink-0 flex-col gap-1 border-r border-line bg-card p-2" aria-label="Side" data-pinned="false">
                <x-side-link :href="route('dashboard')" :current="request()->routeIs('dashboard')">Home</x-side-link>
                <x-side-link :href="route('forex')" :current="request()->routeIs('forex')">Forex</x-side-link>
                <x-side-link :href="route('settings')" :current="request()->routeIs('settings', 'roles*', 'colors*')">Settings</x-side-link>
                <x-side-link :href="route('notes')" :current="request()->routeIs('notes*')">Docs</x-side-link>
                <button type="button" id="side-pin" class="side-pin mt-auto rounded-xl px-4 py-3 text-left text-sm hover:bg-soft">Keep open</button>
            </nav>

            <div class="flex min-w-0 flex-1 flex-col">
                @php
                    $submenuGroup = match (true) {
                        request()->routeIs('settings', 'roles*', 'colors*') => 'settings',
                        request()->routeIs('notes*') => 'docs',
                        request()->routeIs('forex') => 'forex',
                        default => 'home',
                    };
                    $submenus = [
                        'settings' => [
                            ['route' => 'roles', 'label' => 'Roles', 'active' => 'roles*'],
                            ['route' => 'colors', 'label' => 'Colors', 'active' => 'colors*'],
                        ],
                        'docs' => [
                            ['route' => 'notes', 'label' => 'Notes', 'active' => 'notes*'],
                        ],
                    ];
                @endphp
                <section aria-label="Menu" class="flex shrink-0 flex-wrap items-center gap-3 border-b border-line bg-soft px-6 py-3">
                    <x-section-tag number="2" name="Menu" />
                    <nav aria-label="Submenu" class="flex flex-wrap gap-2">
                        @foreach ($submenus[$submenuGroup] ?? [] as $item)
                            <a
                                href="{{ route($item['route']) }}"
                                @class([
                                    'rounded-xl px-3 py-2 text-sm',
                                    'bg-primary font-semibold' => request()->routeIs($item['active'] ?? $item['route']),
                                    'hover:bg-card' => ! request()->routeIs($item['active'] ?? $item['route']),
                                ])
                                @if (request()->routeIs($item['active'] ?? $item['route'])) aria-current="page" @endif
                            >{{ $item['label'] }}</a>
                        @endforeach
                    </nav>
                </section>

                <section aria-label="{{ $title }}" class="flex h-12 shrink-0 items-center gap-4 border-b border-line bg-primary px-6">
                    <p class="text-sm font-semibold tracking-wide">{{ $title }}</p>
                    @isset($panel)
                        <div class="ml-auto flex items-center gap-2">{{ $panel }}</div>
                    @endisset
                </section>

                <div id="desk-stage" class="flex min-h-0 flex-1 flex-col">
                    <main class="min-h-0 flex-1 overflow-auto px-8 py-8">
                        {{ $slot }}
                    </main>
                </div>
                <footer class="flex h-12 shrink-0 items-center gap-4 border-t border-line bg-card px-6 text-sm">
                    <x-section-tag number="4" name="Bottom" />
                    <div id="desk-dock" class="flex min-w-0 flex-1 items-center gap-2 overflow-auto"></div>
                    <p class="shrink-0 text-ink/70">Desk</p>
                </footer>
            </div>
        </div>
        @stack('windows')
        <script>
            const sideMenu = document.getElementById('side-menu');
            const sidePin = document.getElementById('side-pin');
            const pinned = localStorage.getItem('desk-menu-pinned') === 'true';

            sideMenu.dataset.pinned = pinned ? 'true' : 'false';
            sidePin.textContent = pinned ? 'Auto close' : 'Keep open';

            sidePin.addEventListener('click', () => {
                const next = sideMenu.dataset.pinned !== 'true';
                sideMenu.dataset.pinned = next ? 'true' : 'false';
                sidePin.textContent = next ? 'Auto close' : 'Keep open';
                localStorage.setItem('desk-menu-pinned', next ? 'true' : 'false');
            });
        </script>
    </body>
</html>
