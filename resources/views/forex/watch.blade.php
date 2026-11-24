<x-layouts.shell title="Market Watch" :user="$user" fit>
    <x-slot:panel>
        <p id="watch-feed" class="text-xs font-semibold" data-online="{{ $feedOnline ? '1' : '0' }}">
            {{ $feedOnline ? 'Live' : 'Waiting for connector' }}
        </p>
    </x-slot:panel>

    <section class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-2xl border border-line bg-card" data-arrow-pane aria-label="Market watch">
        <button type="button" class="side-arrow w-full border-b border-line bg-soft" data-scroll-up hidden aria-label="Rates above">
            <svg viewBox="0 0 16 16" class="h-4 w-4" aria-hidden="true"><path d="M3.5 10.5 8 6l4.5 4.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path></svg>
        </button>
        <div class="arrow-scroll min-h-0 flex-1" data-arrow-scroll>
            <table class="w-full table-fixed text-left text-xs sm:text-sm">
                <colgroup>
                    <col class="w-[38%]">
                    <col>
                    <col>
                    <col>
                    <col>
                    <col>
                </colgroup>
                <thead class="sticky top-0 z-10 bg-soft">
                    <tr>
                        <th class="px-2 py-2 font-semibold sm:px-4" scope="col">Symbol</th>
                        <th class="px-2 py-2 text-right font-semibold sm:px-4" scope="col">Bid</th>
                        <th class="px-2 py-2 text-right font-semibold sm:px-4" scope="col">Ask</th>
                        <th class="px-2 py-2 text-right font-semibold sm:px-4" scope="col">High</th>
                        <th class="px-2 py-2 text-right font-semibold sm:px-4" scope="col">Low</th>
                        <th class="px-2 py-2 text-right font-semibold sm:px-4" scope="col">Time</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($quotes as $quote)
                        <tr class="border-t border-line" data-quote-row="{{ $quote['symbol'] }}" data-live="{{ $quote['live'] ? '1' : '0' }}">
                            <td class="px-2 py-2 sm:px-4">
                                <span class="font-semibold">{{ $quote['symbol'] }}</span>
                                @php($symbolName = \Modules\Forex\Support\Symbols::name($quote['symbol']))
                                @if ($symbolName !== '')
                                    <span class="ml-2 text-[11px] font-normal leading-tight text-ink/60">{{ $symbolName }}</span>
                                @endif
                            </td>
                            <td class="px-2 py-2 text-right tabular-nums sm:px-4" data-q="bid">{{ $quote['bid'] }}</td>
                            <td class="px-2 py-2 text-right tabular-nums sm:px-4" data-q="ask">{{ $quote['ask'] }}</td>
                            <td class="px-2 py-2 text-right tabular-nums sm:px-4" data-q="high">{{ $quote['high'] }}</td>
                            <td class="px-2 py-2 text-right tabular-nums sm:px-4" data-q="low">{{ $quote['low'] }}</td>
                            <td class="px-2 py-2 text-right tabular-nums sm:px-4" data-q="time">{{ $quote['time'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <button type="button" class="side-arrow w-full border-t border-line bg-soft" data-scroll-down hidden aria-label="Rates below">
            <svg viewBox="0 0 16 16" class="h-4 w-4" aria-hidden="true"><path d="M3.5 5.5 8 10l4.5-4.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path></svg>
        </button>
    </section>

    <script>
        const quotesUrl = @json(route('forex.market-watch.quotes'));
        const bridgeQuotesUrl = 'http://127.0.0.1:8766/quotes';
        const feed = document.getElementById('watch-feed');
        let ticksInFlight = false;
        let bridgeLive = true;
        let lastDeskPull = 0;

        const paintTicks = (payload) => {
            let anyLive = false;

            (payload.quotes || []).forEach((quote) => {
                const row = document.querySelector(`[data-quote-row="${quote.symbol}"]`);

                if (! row) {
                    return;
                }

                row.dataset.live = quote.live ? '1' : '0';
                anyLive = anyLive || Boolean(quote.live);

                ['bid', 'ask', 'high', 'low', 'time'].forEach((key) => {
                    const cell = row.querySelector(`[data-q="${key}"]`);

                    if (cell && quote[key] !== undefined) {
                        cell.textContent = quote[key];
                    }
                });
            });

            if (feed) {
                feed.dataset.online = anyLive ? '1' : '0';
                feed.textContent = anyLive ? 'Live' : 'Waiting for connector';
            }
        };

        const pullDesk = async () => {
            const now = Date.now();

            if (now - lastDeskPull < 200) {
                return;
            }

            lastDeskPull = now;
            const response = await fetch(`${quotesUrl}?ticks=1`, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });

            if (response.ok) {
                paintTicks(await response.json());
            }
        };

        const refreshTicks = async () => {
            if (ticksInFlight) {
                return;
            }

            ticksInFlight = true;

            try {
                if (bridgeLive) {
                    const response = await fetch(bridgeQuotesUrl, {
                        cache: 'no-store',
                        signal: AbortSignal.timeout(80),
                    });

                    if (response.ok) {
                        paintTicks(await response.json());
                        return;
                    }

                    bridgeLive = false;
                }

                await pullDesk();
            } catch (error) {
                bridgeLive = false;

                try {
                    await pullDesk();
                } catch (deskError) {
                    // Keep the last painted rates if both feeds fail.
                }
            } finally {
                ticksInFlight = false;
            }
        };

        setInterval(refreshTicks, 20);
        setInterval(() => {
            bridgeLive = true;
        }, 2000);
        refreshTicks();
    </script>
</x-layouts.shell>
