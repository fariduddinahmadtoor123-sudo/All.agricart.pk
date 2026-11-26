<x-layouts.shell title="Calendar" :user="$user" fit :heading="false">
    <x-slot:filters>
        <form method="GET" action="{{ route('forex.calendar') }}" class="flex flex-wrap items-center gap-2" aria-label="Calendar filters">
            <label class="flex items-center gap-1.5 text-xs font-semibold">
                <span>Currency</span>
                <select name="currency" onchange="this.form.submit()" class="h-8 rounded-xl border border-line bg-card px-2 text-xs font-semibold text-ink">
                    @foreach ($currencies as $value => $label)
                        <option value="{{ $value }}" @selected($currency === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="flex items-center gap-1.5 text-xs font-semibold">
                <span>Impact</span>
                <select name="impact" onchange="this.form.submit()" class="h-8 rounded-xl border border-line bg-card px-2 text-xs font-semibold text-ink">
                    @foreach ($impacts as $value => $label)
                        <option value="{{ $value }}" @selected($impact === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="flex items-center gap-1.5 text-xs font-semibold">
                <span>Day</span>
                <select name="range" onchange="this.form.submit()" class="h-8 rounded-xl border border-line bg-card px-2 text-xs font-semibold text-ink">
                    @foreach ($ranges as $value => $label)
                        <option value="{{ $value }}" @selected($range === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </form>
    </x-slot:filters>

    <div class="grid min-h-0 flex-1 grid-cols-1 items-start gap-3 xl:grid-cols-[minmax(0,43rem)_minmax(11rem,1fr)_minmax(0,43rem)] xl:items-stretch">
        <section class="flex h-full min-h-[28rem] min-w-0 flex-col overflow-hidden rounded-2xl border border-line bg-card xl:min-h-0" aria-label="{{ $rangeLabel }}">
            <div class="flex h-9 shrink-0 items-center border-b border-line bg-soft px-3">
                <h2 class="text-sm font-semibold">{{ $rangeLabel }}</h2>
            </div>
            <iframe
                id="economic-calendar"
                title="Investing.com economic calendar, {{ $rangeLabel }}"
                data-src="{{ $todayUrl }}"
                class="min-h-0 w-full flex-1 border-0"
                loading="eager"
                referrerpolicy="strict-origin-when-cross-origin"
            ></iframe>
            <script>
                (() => {
                    const zones = {
                        '-660': 2, '-600': 3, '-540': 4, '-480': 5, '-420': 37, '-360': 39,
                        '-300': 42, '-240': 10, '-210': 11, '-180': 48, '-60': 53, '0': 55,
                        '60': 60, '120': 67, '180': 70, '210': 19, '240': 20, '270': 21,
                        '300': 77, '330': 23, '345': 24, '360': 25, '390': 26, '420': 27,
                        '480': 28, '540': 29, '570': 90, '600': 91, '630': 30, '660': 31,
                        '720': 1, '780': 33,
                    };
                    const minutes = -new Date().getTimezoneOffset();
                    const keys = Object.keys(zones).map(Number);
                    const nearest = keys.reduce((best, key) => Math.abs(key - minutes) < Math.abs(best - minutes) ? key : best, keys[0]);
                    const frame = document.getElementById('economic-calendar');
                    const url = new URL(frame.dataset.src);
                    url.searchParams.set('timeZone', String(zones[String(nearest)]));
                    frame.src = url.toString();
                })();
            </script>
            <p class="shrink-0 px-3 py-1.5 text-[11px] text-ink/70">
                Economic Calendar by
                <a href="https://www.investing.com/" rel="nofollow noopener" target="_blank" class="font-semibold text-ink underline">Investing.com</a>
            </p>
        </section>

        <section class="h-fit w-full rounded-2xl border border-line bg-card p-3 xl:self-start" aria-label="Legend">
            <h2 class="text-sm font-semibold">Legend</h2>
            <ul class="mt-3 grid gap-2.5 text-xs">
                <li class="flex items-center gap-2">
                    <svg viewBox="0 0 16 16" class="h-4 w-4 shrink-0" aria-hidden="true">
                        <path fill="currentColor" d="M2 6.2h2.1L7.2 3.4v9.2L4.1 9.8H2V6.2z"></path>
                        <path fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" d="M9.2 6c.7.5 1.1 1.2 1.1 2s-.4 1.5-1.1 2M10.8 4.6c1.1.9 1.7 2 1.7 3.4s-.6 2.5-1.7 3.4"></path>
                    </svg>
                    <span>Speech</span>
                </li>
                <li class="flex items-center gap-2">
                    <span class="grid h-4 w-4 shrink-0 place-items-center text-xs font-bold" aria-hidden="true">P</span>
                    <span>Preliminary Release</span>
                </li>
                <li class="flex items-center gap-2">
                    <span class="h-2.5 w-2.5 shrink-0 rounded-full bg-amber-400" aria-hidden="true"></span>
                    <span>Revised Release</span>
                </li>
                <li class="flex items-center gap-2">
                    <svg viewBox="0 0 16 16" class="h-4 w-4 shrink-0" aria-hidden="true">
                        <path fill="currentColor" d="M4 1.5h8v1.4H4zm0 11.6h8V14.5H4zM4.8 3.2h6.4L8.6 6.4 11.2 9v.4H4.8V9l2.6-2.6L4.8 3.2zm1.2 8.2h4l-2-2.2-2 2.2z"></path>
                    </svg>
                    <span>Retrieving Data</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="flex shrink-0 items-center gap-0.5 pt-0.5" aria-hidden="true">
                        <x-bull-mark />
                        <x-bull-mark muted />
                        <x-bull-mark muted />
                    </span>
                    <span>Low Volatility Expected</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="flex shrink-0 items-center gap-0.5 pt-0.5" aria-hidden="true">
                        <x-bull-mark />
                        <x-bull-mark />
                        <x-bull-mark muted />
                    </span>
                    <span>Moderate Volatility Expected</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="flex shrink-0 items-center gap-0.5 pt-0.5" aria-hidden="true">
                        <x-bull-mark />
                        <x-bull-mark />
                        <x-bull-mark />
                    </span>
                    <span>High Volatility Expected</span>
                </li>
            </ul>
        </section>

        <section class="flex h-auto min-h-0 flex-col overflow-hidden rounded-2xl border border-line bg-card xl:h-full" aria-label="High impact">
            <div class="flex h-9 shrink-0 items-center border-b border-line bg-soft px-3">
                <h2 class="text-sm font-semibold">High impact</h2>
            </div>
            <ol class="m-0 flex min-h-0 flex-1 list-none flex-col p-0">
                @foreach ($highImpact as $index => $item)
                    <li class="flex min-h-0 flex-1 items-center gap-2 border-b border-line px-3 last:border-b-0">
                        <span class="w-4 shrink-0 text-[11px] font-semibold tabular-nums text-ink/50">{{ $index + 1 }}</span>
                        <span class="flex shrink-0 items-center" aria-hidden="true">
                            <x-bull-mark red class="h-4 w-4" />
                            <x-bull-mark red class="h-4 w-4" />
                            <x-bull-mark red class="h-4 w-4" />
                        </span>
                        <span class="min-w-0 leading-tight">
                            <span class="block text-xs font-semibold">{{ $item['name'] }}</span>
                            <span class="block text-[11px] text-ink/70">{{ $item['moves'] }}</span>
                        </span>
                    </li>
                @endforeach
            </ol>
        </section>
    </div>
</x-layouts.shell>
