@props(['title', 'user', 'fit' => false, 'heading' => true])

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
        <x-corner-boot />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="flex h-screen flex-col bg-canvas font-sans text-ink antialiased">
        <header class="flex h-12 shrink-0 items-center justify-between gap-3 border-b border-line bg-card px-3 sm:px-6">
            <div class="flex min-w-0 items-center gap-3">
                <x-section-tag number="1" name="Top" />
                <p class="text-lg font-semibold tracking-wide">Desk</p>
                @php
                    $moduleLabel = \App\Support\DeskMenu::labelForRoute(request()->route()?->getName());
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
                            ['label' => 'Appearance', 'route' => 'colors'],
                            ['label' => 'New color'],
                        ],
                        request()->routeIs('colors.edit') => [
                            ['label' => 'Settings', 'route' => 'settings'],
                            ['label' => 'Appearance', 'route' => 'colors'],
                            ['label' => 'Edit color'],
                        ],
                        request()->routeIs('colors') => [
                            ['label' => 'Settings', 'route' => 'settings'],
                            ['label' => 'Appearance'],
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
                        request()->routeIs('forex.mt5-accounts.create') => [
                            ['label' => 'Forex Trading', 'route' => 'forex.market-watch'],
                            ['label' => 'MT5 accounts', 'route' => 'forex.mt5-accounts'],
                            ['label' => 'New MT5 account'],
                        ],
                        request()->routeIs('forex.mt5-accounts*') => [
                            ['label' => 'Forex Trading', 'route' => 'forex.market-watch'],
                            ['label' => 'MT5 accounts'],
                        ],
                        request()->routeIs('forex.client-accounts.create') => [
                            ['label' => 'Forex Trading', 'route' => 'forex.market-watch'],
                            ['label' => 'Client accounts', 'route' => 'forex.client-accounts'],
                            ['label' => 'Open account'],
                        ],
                        request()->routeIs('forex.client-accounts*') => [
                            ['label' => 'Forex Trading', 'route' => 'forex.market-watch'],
                            ['label' => 'Client accounts'],
                        ],
                        request()->routeIs('forex.ledger.create') => [
                            ['label' => 'Forex Trading', 'route' => 'forex.market-watch'],
                            ['label' => 'Ledger', 'route' => 'forex.ledger'],
                            ['label' => 'Post entry'],
                        ],
                        request()->routeIs('forex.ledger*') => [
                            ['label' => 'Forex Trading', 'route' => 'forex.market-watch'],
                            ['label' => 'Ledger'],
                        ],
                        request()->routeIs('forex.watch') => [
                            ['label' => 'Forex Trading', 'route' => 'forex.market-watch'],
                            ['label' => 'Market Watch'],
                        ],
                        request()->routeIs('forex*') => [
                            ['label' => 'Forex Trading', 'route' => 'forex.market-watch'],
                            ['label' => 'Trade Terminal'],
                        ],
                        is_string($moduleLabel) => [
                            ['label' => $moduleLabel],
                        ],
                        default => [
                            ['label' => 'Dashboard'],
                        ],
                    };
                @endphp
                <nav aria-label="Breadcrumb" class="flex min-w-0 items-center gap-2 truncate text-sm">
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
            <div class="flex shrink-0 items-center gap-2 sm:gap-4">
                <p class="hidden text-sm sm:block">{{ $user->name }}</p>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-xl border border-line bg-canvas px-3 py-1.5 text-sm font-semibold">Log out</button>
                </form>
            </div>
        </header>

        <div class="flex min-h-0 flex-1">
            <div id="side-rail" class="side-rail flex shrink-0 flex-col border-r border-line bg-card p-2" data-pinned="false">
                <button type="button" id="side-pin" class="mb-1 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl hover:bg-soft" aria-label="Keep open" title="Keep open" aria-pressed="false">
                    <svg viewBox="0 0 16 16" class="h-4 w-4" aria-hidden="true">
                        <rect x="1.5" y="2" width="13" height="12" rx="1.5" fill="none" stroke="currentColor" stroke-width="1.4"></rect>
                        <path d="M6 2.2v11.6" stroke="currentColor" stroke-width="1.4"></path>
                    </svg>
                </button>
                <button type="button" id="side-up" class="side-arrow" hidden aria-label="Menus above">
                    <svg viewBox="0 0 16 16" class="h-4 w-4" aria-hidden="true">
                        <path d="M3.5 10.5 8 6l4.5 4.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                </button>
                <nav id="side-menu" class="side-menu flex min-h-0 flex-1 flex-col gap-1" aria-label="Side">
                @foreach (\App\Support\DeskMenu::items() as $item)
                    <x-side-link :href="route($item['route'], $item['parameters'])" :current="\App\Support\DeskMenu::isCurrent($item)">
                        {{ $item['label'] }}
                        @if ($item['soon'])
                            <span class="text-[10px] font-semibold tracking-wide text-ink/50">Soon</span>
                        @endif
                    </x-side-link>
                @endforeach
                </nav>
                <button type="button" id="side-down" class="side-arrow" hidden aria-label="Menus below">
                    <svg viewBox="0 0 16 16" class="h-4 w-4" aria-hidden="true">
                        <path d="M3.5 5.5 8 10l4.5-4.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                </button>
            </div>

            <div class="flex min-w-0 flex-1 flex-col">
                @php
                    $submenuGroup = match (true) {
                        request()->routeIs('settings', 'roles*', 'colors*') => 'settings',
                        request()->routeIs('notes*') => 'docs',
                        request()->routeIs('forex*') => 'forex',
                        default => 'home',
                    };
                    $submenus = [
                        'settings' => [
                            ['route' => 'roles', 'label' => 'Roles', 'active' => 'roles*'],
                            ['route' => 'colors', 'label' => 'Appearance', 'active' => 'colors*'],
                        ],
                        'docs' => [
                            ['route' => 'notes', 'label' => 'Notes', 'active' => 'notes*'],
                        ],
                        'forex' => [
                            ['route' => 'forex.watch', 'label' => 'Market Watch', 'active' => 'forex.watch'],
                            ['route' => 'forex.market-watch', 'label' => 'Trade Terminal', 'active' => 'forex.market-watch'],
                            ['route' => 'forex.client-accounts', 'label' => 'Client accounts', 'active' => 'forex.client-accounts*'],
                            ['route' => 'forex.mt5-accounts', 'label' => 'MT5 accounts', 'active' => 'forex.mt5-accounts*'],
                            ['route' => 'forex.ledger', 'label' => 'Ledger', 'active' => 'forex.ledger*'],
                        ],
                    ];
                @endphp
                <section aria-label="Menu" class="flex shrink-0 flex-wrap items-center gap-2 border-b border-line bg-soft px-3 py-2 sm:px-6">
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

                <section aria-label="{{ $title }}" class="flex min-h-12 shrink-0 flex-wrap items-center gap-2 border-b border-line bg-primary px-3 py-1.5 sm:px-6">
                    @if ($heading)
                        <p class="shrink-0 text-sm font-semibold tracking-wide">{{ $title }}</p>
                    @endif
                    <div class="flex min-w-0 flex-1 flex-wrap items-center justify-center gap-1.5">
                        @isset($meters)
                            {{ $meters }}
                        @endisset
                    </div>
                    @isset($panel)
                        <div class="flex shrink-0 items-center gap-2">{{ $panel }}</div>
                    @endisset
                </section>

                <div id="desk-stage" class="flex min-h-0 flex-1 flex-col">
                    <main @class([
                        'min-h-0 flex-1',
                        'overflow-auto px-4 py-4 sm:px-8 sm:py-6' => ! $fit,
                        'flex flex-col overflow-hidden px-3 py-2 sm:px-4 sm:py-3' => $fit,
                    ])>
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
            const sideRail = document.getElementById('side-rail');
            const sideMenu = document.getElementById('side-menu');
            const sidePin = document.getElementById('side-pin');
            const sideUp = document.getElementById('side-up');
            const sideDown = document.getElementById('side-down');

            const updateArrows = () => {
                const above = sideMenu.scrollTop > 2;
                const below = sideMenu.scrollTop + sideMenu.clientHeight < sideMenu.scrollHeight - 2;

                sideUp.hidden = ! above;
                sideDown.hidden = ! below;
            };

            const scrollMenu = (direction) => {
                sideMenu.scrollBy({
                    top: direction * Math.max(72, sideMenu.clientHeight * 0.7),
                    behavior: 'smooth',
                });
            };

            const applyPin = (next, store) => {
                const label = next ? 'Auto close' : 'Keep open';

                sideRail.dataset.pinned = next ? 'true' : 'false';
                sidePin.setAttribute('aria-label', label);
                sidePin.setAttribute('title', label);
                sidePin.setAttribute('aria-pressed', next ? 'true' : 'false');

                if (store) {
                    localStorage.setItem('desk-menu-pinned', next ? 'true' : 'false');
                }

                requestAnimationFrame(updateArrows);
            };

            applyPin(localStorage.getItem('desk-menu-pinned') === 'true', false);

            sidePin.addEventListener('click', () => {
                applyPin(sideRail.dataset.pinned !== 'true', true);
            });
            sideUp.addEventListener('click', () => scrollMenu(-1));
            sideDown.addEventListener('click', () => scrollMenu(1));
            sideMenu.addEventListener('scroll', updateArrows);
            sideRail.addEventListener('mouseenter', () => requestAnimationFrame(updateArrows));
            sideRail.addEventListener('transitionend', updateArrows);
            window.addEventListener('resize', updateArrows);
        </script>
    </body>
</html>
