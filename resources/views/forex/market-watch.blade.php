<x-layouts.shell title="Trade Terminal" :user="$user" fit :heading="false">
    <x-slot:meters>
        @foreach ($account as $meter)
            <div class="flex h-8 shrink-0 items-center gap-2 rounded-xl border border-line bg-white px-2.5 text-black" data-meter="{{ $meter['label'] }}">
                <span class="text-xs font-medium">{{ $meter['label'] }}</span>
                <span
                    data-meter-value
                    @class([
                        'text-xs font-semibold tabular-nums',
                        'text-emerald-800' => $meter['tone'] === 'profit',
                        'text-red-800' => $meter['tone'] === 'loss',
                    ])
                >{{ $meter['value'] }}</span>
            </div>
        @endforeach

        <div id="market-sessions" class="flex h-8 shrink-0 flex-col justify-center gap-0.5" aria-label="Market sessions">
            <div class="flex items-center gap-0.5">
                @foreach ($sessions as $session)
                    <span
                        data-session="{{ $session['key'] }}"
                        data-open="{{ $session['open'] }}"
                        data-close="{{ $session['close'] }}"
                        @class([
                            'flex h-3.5 shrink-0 items-center rounded-md px-1.5 text-[9px] font-semibold leading-none',
                            'bg-emerald-700 text-white' => $session['active'],
                            'border border-line bg-white text-ink' => ! $session['active'],
                        ])
                    >{{ $session['label'] }}</span>
                @endforeach
            </div>
            <div class="relative h-1.5 w-full min-w-36 overflow-hidden rounded-full bg-black/10" aria-label="Liquidity {{ $liquidity['level'] }}" data-liquidity-track>
                <span
                    data-liquidity-fill
                    class="absolute inset-y-0 left-0 rounded-full transition-[width,background-color] duration-300"
                    style="width: {{ $liquidity['percent'] }}%; background-color: {{ $liquidity['color'] }}"
                ></span>
            </div>
        </div>
    </x-slot:meters>

    <x-slot:panel>
        <div class="flex flex-wrap items-center gap-2">
            <label class="flex items-center gap-2 text-xs font-semibold">
                <input type="checkbox" id="fast-trade-master" class="h-4 w-4" aria-label="Fast trading">
                Fast trading
            </label>
            <p id="fast-trade-hint" class="hidden text-[11px] font-medium text-ink/70" role="status">
                Fast trading: arm a row, then one click buys or sells.
            </p>
            <span class="text-[11px] font-medium text-ink/60">
                {{ count($watchlist) }} on wishlist
                @if ($feedOnline)
                    · live feed
                @else
                    · waiting for connector
                @endif
            </span>
        </div>
    </x-slot:panel>

    @if ($errors->any())
        <p class="mb-3 text-sm text-red-800">{{ $errors->first() }}</p>
    @endif

    <div id="watch-board" class="flex min-h-0 flex-1 flex-col gap-2 xl:flex-row">
    <div id="watch-main" class="flex min-h-0 min-w-0 flex-[1.6] flex-col gap-2">
    <section class="flex min-h-0 flex-[1.4] flex-col overflow-hidden rounded-2xl border border-line bg-card" data-arrow-pane data-pane="quotes" data-pane-title="Symbols">
        <div class="flex shrink-0 flex-wrap items-center gap-1 border-b border-line px-2 py-1 sm:gap-2">
            <div class="flex min-w-0 flex-1 flex-wrap items-center gap-1" role="tablist" aria-label="Symbol sessions">
                <button
                    type="button"
                    data-symbol-session="wishlist"
                    data-window-open="watchlist-window"
                    class="rounded-lg bg-primary px-2 py-1 text-[11px] font-semibold sm:px-2.5 sm:text-xs"
                    role="tab"
                    aria-selected="true"
                >My Wishlist</button>
                @foreach ($categories as $category)
                    <button
                        type="button"
                        data-symbol-session="{{ $category['key'] }}"
                        class="rounded-lg px-2 py-1 text-[11px] font-semibold hover:bg-soft sm:px-2.5 sm:text-xs"
                        role="tab"
                        aria-selected="false"
                    >{{ $category['label'] }}</button>
                @endforeach
            </div>
            @include('forex.partials.pane-controls', ['title' => 'Symbols'])
        </div>
        <button type="button" class="side-arrow w-full border-b border-line bg-soft" data-scroll-up hidden aria-label="Quotes above">
            <svg viewBox="0 0 16 16" class="h-4 w-4" aria-hidden="true">
                <path d="M3.5 10.5 8 6l4.5 4.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path>
            </svg>
        </button>
        <div class="arrow-scroll min-h-0 flex-1" data-arrow-scroll>
        <table class="w-max min-w-full whitespace-nowrap text-left text-xs">
            <thead class="sticky top-0 z-10 bg-soft">
                <tr>
                    <th class="px-2 py-1.5 font-semibold" rowspan="2" scope="col">Symbol</th>
                    <th class="px-2 py-1.5 text-right font-semibold" rowspan="2" scope="col">Bid</th>
                    <th class="px-2 py-1.5 text-right font-semibold" rowspan="2" scope="col">Ask</th>
                    <th class="px-2 py-1.5 text-right font-semibold" rowspan="2" scope="col">Spread</th>
                    <th class="px-2 py-1.5 text-right font-semibold" rowspan="2" scope="col">Low</th>
                    <th class="px-2 py-1.5 text-right font-semibold" rowspan="2" scope="col">Range</th>
                    <th class="px-2 py-1.5 text-right font-semibold" rowspan="2" scope="col">High</th>
                    <th class="px-2 py-1.5 text-right font-semibold" rowspan="2" scope="col">Time</th>
                    <th class="px-2 py-1.5 text-center font-semibold" rowspan="2" scope="col">Quantity</th>
                    <th class="px-2 py-1.5 text-right font-semibold" rowspan="2" scope="col">Lots</th>
                    <th class="px-2 py-1.5 text-right font-semibold" rowspan="2" scope="col">P/L</th>
                    <th class="px-1 pt-1 pb-0 text-center text-[10px] font-medium text-ink/70" colspan="3" scope="colgroup">Close lots for this symbol</th>
                </tr>
                <tr>
                    <th class="px-1 pb-1 text-center text-[10px] font-semibold text-emerald-800" scope="col">Profit</th>
                    <th class="px-1 pb-1 text-center text-[10px] font-semibold text-red-800" scope="col">Loss</th>
                    <th class="px-1 pb-1 text-center text-[10px] font-semibold" scope="col">All</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($quotes as $quote)
                    <tr
                        class="border-t border-line"
                        data-quote-row="{{ $quote['symbol'] }}"
                        data-category="{{ $quote['category'] }}"
                        data-in-wishlist="{{ in_array($quote['symbol'], $watchlist, true) ? '1' : '0' }}"
                        data-live="{{ $quote['live'] ? '1' : '0' }}"
                        data-broker-open="{{ $quote['broker_open'] ? '1' : '0' }}"
                        @if (! in_array($quote['symbol'], $watchlist, true)) hidden @endif
                    >
                        <td class="px-2 py-1 font-semibold">
                            <span>{{ $quote['symbol'] }}</span>
                            @if (! $quote['broker_open'])
                                <span class="mt-0.5 block text-[10px] font-medium text-red-800" data-q="broker-note">Broker closed — no rates</span>
                            @endif
                        </td>
                        <td class="px-2 py-1 text-right tabular-nums" data-q="bid">{{ $quote['bid'] }}</td>
                        <td class="px-2 py-1 text-right tabular-nums" data-q="ask">{{ $quote['ask'] }}</td>
                        <td class="px-2 py-1 text-right tabular-nums" data-q="spread">{{ $quote['spread'] }}</td>
                        <td class="px-2 py-1 text-right tabular-nums" data-q="low">{{ $quote['low'] }}</td>
                        <td class="px-2 py-1 text-right tabular-nums" data-q="range">{{ $quote['range'] }}</td>
                        <td class="px-2 py-1 text-right tabular-nums" data-q="high">{{ $quote['high'] }}</td>
                        <td class="px-2 py-1 text-right tabular-nums" data-q="time">{{ $quote['time'] }}</td>
                        <td class="px-2 py-1">
                            <form method="POST" action="{{ route('forex.positions.open') }}" class="flex flex-wrap items-center justify-center gap-1">
                                @csrf
                                <input type="hidden" name="symbol" value="{{ $quote['symbol'] }}">
                                <input type="checkbox" name="armed" value="1" data-fast-arm class="h-4 w-4" aria-label="Arm {{ $quote['symbol'] }}">
                                <input
                                    type="number"
                                    name="quantity"
                                    value="0.01"
                                    min="0.01"
                                    step="0.01"
                                    inputmode="decimal"
                                    class="h-6 w-14 rounded-lg border border-line bg-canvas px-1 text-center text-xs tabular-nums text-ink outline-none focus:border-ink/40"
                                    aria-label="Quantity for {{ $quote['symbol'] }}"
                                >
                                <button
                                    type="submit"
                                    name="side"
                                    value="buy"
                                    data-fast-trade
                                    disabled
                                    class="grid h-6 place-items-center rounded-lg bg-emerald-800 px-2 text-[11px] font-semibold text-white disabled:cursor-default disabled:opacity-30"
                                    aria-label="Buy {{ $quote['symbol'] }}"
                                >Buy</button>
                                <button
                                    type="submit"
                                    name="side"
                                    value="sell"
                                    data-fast-trade
                                    disabled
                                    class="grid h-6 place-items-center rounded-lg bg-red-800 px-2 text-[11px] font-semibold text-white disabled:cursor-default disabled:opacity-30"
                                    aria-label="Sell {{ $quote['symbol'] }}"
                                >Sell</button>
                            </form>
                        </td>
                        <td class="px-2 py-1 text-right leading-4" data-q="lots">
                            <p data-q="sell-lots">Sell = {{ $quote['sell_lots'] }}</p>
                            <p data-q="buy-lots">Buy = {{ $quote['buy_lots'] }}</p>
                        </td>
                        <td
                            data-q="profit"
                            @class([
                                'px-2 py-1 text-right font-semibold tabular-nums',
                                'text-emerald-800' => str_starts_with($quote['profit'], '+'),
                                'text-red-800' => str_starts_with($quote['profit'], '-'),
                            ])
                        >{{ $quote['profit'] }}</td>
                        <td class="px-1 py-1 text-center" data-close="profit">
                            @include('forex.partials.close', ['quote' => $quote, 'side' => 'profit', 'label' => 'Close profitable '.$quote['symbol'].' trades', 'enabled' => $quote['profit_count'] > 0])
                        </td>
                        <td class="px-1 py-1 text-center" data-close="loss">
                            @include('forex.partials.close', ['quote' => $quote, 'side' => 'loss', 'label' => 'Close losing '.$quote['symbol'].' trades', 'enabled' => $quote['loss_count'] > 0])
                        </td>
                        <td class="px-1 py-1 text-center" data-close="all">
                            @include('forex.partials.close', ['quote' => $quote, 'side' => 'all', 'label' => 'Close all '.$quote['symbol'].' trades', 'enabled' => $quote['open_count'] > 0])
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
        <button type="button" class="side-arrow w-full border-t border-line bg-soft" data-scroll-down hidden aria-label="Quotes below">
            <svg viewBox="0 0 16 16" class="h-4 w-4" aria-hidden="true">
                <path d="M3.5 5.5 8 10l4.5-4.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path>
            </svg>
        </button>
    </section>

    <section class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-2xl border border-line bg-card" data-arrow-pane data-pane="orders" data-pane-title="Orders" aria-label="Orders">
        <div class="flex shrink-0 flex-wrap items-center gap-2 border-b border-line px-3 py-1">
            <h2 class="text-xs font-semibold">Orders</h2>
            <div class="ml-auto flex items-center gap-1">
                <div class="flex gap-1" role="tablist" aria-label="Order lists">
                    <button type="button" class="rounded-xl px-2.5 py-1 text-xs" role="tab" data-order-tab="open" aria-selected="true">Open</button>
                    <button type="button" class="rounded-xl px-2.5 py-1 text-xs" role="tab" data-order-tab="pending" aria-selected="false">Pending</button>
                    <button type="button" class="rounded-xl px-2.5 py-1 text-xs" role="tab" data-order-tab="history" aria-selected="false">History</button>
                </div>
                @include('forex.partials.pane-controls', ['title' => 'Orders'])
            </div>
        </div>
        <button type="button" class="side-arrow w-full border-b border-line bg-soft" data-scroll-up hidden aria-label="Orders above">
            <svg viewBox="0 0 16 16" class="h-4 w-4" aria-hidden="true">
                <path d="M3.5 10.5 8 6l4.5 4.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path>
            </svg>
        </button>
        <div class="arrow-scroll min-h-0 flex-1" data-arrow-scroll>
            @foreach ([
                'open' => 'No open orders.',
                'pending' => 'No pending orders.',
                'history' => 'No closed orders.',
            ] as $list => $empty)
                <div data-order-panel="{{ $list }}" @if ($list !== 'open') hidden @endif>
                    @if ($orders[$list] === [])
                        <p class="px-3 py-3 text-xs text-ink/70">{{ $empty }}</p>
                    @else
                        <table class="w-max min-w-full whitespace-nowrap text-left text-xs">
                            <thead class="sticky top-0 bg-soft">
                                <tr>
                                    <th class="px-2 py-1.5 font-semibold" scope="col">Ticket</th>
                                    <th class="px-2 py-1.5 font-semibold" scope="col">Symbol</th>
                                    <th class="px-2 py-1.5 font-semibold" scope="col">Type</th>
                                    <th class="px-2 py-1.5 font-semibold" scope="col">Side</th>
                                    <th class="px-2 py-1.5 text-right font-semibold" scope="col">Lots</th>
                                    @if ($list === 'pending')
                                        <th class="px-2 py-1.5 text-right font-semibold" scope="col">Price</th>
                                    @else
                                        <th class="px-2 py-1.5 text-right font-semibold" scope="col">Entry</th>
                                    @endif
                                    @if ($list === 'history')
                                        <th class="px-2 py-1.5 text-right font-semibold" scope="col">Close</th>
                                        <th class="px-2 py-1.5 font-semibold" scope="col">Result</th>
                                    @endif
                                    <th class="px-2 py-1.5 text-right font-semibold" scope="col">S/L</th>
                                    <th class="px-2 py-1.5 text-right font-semibold" scope="col">T/P</th>
                                    @if ($list !== 'pending')
                                        <th class="px-2 py-1.5 text-right font-semibold" scope="col">Commission</th>
                                        <th class="px-2 py-1.5 text-right font-semibold" scope="col">P/L</th>
                                    @endif
                                    <th class="px-2 py-1.5 text-right font-semibold" scope="col">Time</th>
                                    @if ($list === 'open' || $list === 'pending')
                                        <th class="px-2 py-1.5 text-center font-semibold" scope="col">{{ $list === 'open' ? 'Close' : 'Cancel' }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($orders[$list] as $order)
                                    <tr class="border-t border-line" data-order-row="{{ $order['id'] }}">
                                        <td class="px-2 py-1 font-semibold tabular-nums">{{ $order['ticket'] }}</td>
                                        <td class="px-2 py-1 font-semibold">{{ $order['symbol'] }}</td>
                                        <td class="px-2 py-1">{{ $order['type'] }}</td>
                                        <td class="px-2 py-1">{{ $order['side'] }}</td>
                                        <td class="px-2 py-1 text-right tabular-nums">{{ $order['quantity'] }}</td>
                                        <td class="px-2 py-1 text-right tabular-nums">{{ $list === 'pending' ? $order['trigger'] : $order['entry'] }}</td>
                                        @if ($list === 'history')
                                            <td class="px-2 py-1 text-right tabular-nums">{{ $order['close'] }}</td>
                                            <td class="px-2 py-1">{{ $order['result'] }}</td>
                                        @endif
                                        @if ($order['can_edit_levels'])
                                            <td class="px-2 py-1" colspan="2">
                                                <form method="POST" action="{{ route('forex.orders.levels', $order['id']) }}" class="flex items-center justify-end gap-1">
                                                    @csrf
                                                    <input
                                                        type="number"
                                                        name="stop_loss"
                                                        value="{{ $order['stop_loss_raw'] }}"
                                                        min="0"
                                                        step="any"
                                                        inputmode="decimal"
                                                        placeholder="S/L"
                                                        class="h-6 w-16 rounded-lg border border-line bg-canvas px-1 text-right text-xs tabular-nums text-ink outline-none focus:border-ink/40"
                                                        aria-label="Stop loss for {{ $order['ticket'] }}"
                                                    >
                                                    <input
                                                        type="number"
                                                        name="take_profit"
                                                        value="{{ $order['take_profit_raw'] }}"
                                                        min="0"
                                                        step="any"
                                                        inputmode="decimal"
                                                        placeholder="T/P"
                                                        class="h-6 w-16 rounded-lg border border-line bg-canvas px-1 text-right text-xs tabular-nums text-ink outline-none focus:border-ink/40"
                                                        aria-label="Take profit for {{ $order['ticket'] }}"
                                                    >
                                                    <button
                                                        type="submit"
                                                        class="grid h-6 place-items-center rounded-lg border border-line bg-canvas px-2 text-[10px] font-semibold text-ink hover:bg-soft"
                                                        aria-label="Save levels for {{ $order['ticket'] }}"
                                                    >Set</button>
                                                </form>
                                            </td>
                                        @else
                                            <td class="px-2 py-1 text-right tabular-nums">{{ $order['stop_loss'] }}</td>
                                            <td class="px-2 py-1 text-right tabular-nums">{{ $order['take_profit'] }}</td>
                                        @endif
                                        @if ($list !== 'pending')
                                            <td class="px-2 py-1 text-right tabular-nums">{{ $order['commission'] }}</td>
                                            <td @class([
                                                'px-2 py-1 text-right font-semibold tabular-nums',
                                                'text-emerald-800' => str_starts_with($order['profit'], '+'),
                                                'text-red-800' => str_starts_with($order['profit'], '-'),
                                            ])>{{ $order['profit'] }}</td>
                                        @endif
                                        <td class="px-2 py-1 text-right tabular-nums">{{ $order['time'] }}</td>
                                        @if ($list === 'open')
                                            <td class="px-2 py-1 text-center">
                                                <form method="POST" action="{{ route('forex.orders.close', $order['id']) }}">
                                                    @csrf
                                                    <button
                                                        type="submit"
                                                        class="grid h-6 w-6 place-items-center rounded-lg border border-line bg-canvas text-ink hover:bg-soft"
                                                        aria-label="Close order {{ $order['ticket'] }}"
                                                        title="Close order"
                                                    >
                                                        <svg viewBox="0 0 16 16" class="h-3 w-3" aria-hidden="true">
                                                            <path d="M4 4l8 8M12 4l-8 8" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"></path>
                                                        </svg>
                                                    </button>
                                                </form>
                                            </td>
                                        @elseif ($list === 'pending')
                                            <td class="px-2 py-1 text-center">
                                                <form method="POST" action="{{ route('forex.orders.cancel', $order['id']) }}">
                                                    @csrf
                                                    <button
                                                        type="submit"
                                                        class="grid h-6 w-6 place-items-center rounded-lg border border-line bg-canvas text-ink hover:bg-soft"
                                                        aria-label="Cancel pending {{ $order['ticket'] }}"
                                                        title="Cancel pending"
                                                    >
                                                        <svg viewBox="0 0 16 16" class="h-3 w-3" aria-hidden="true">
                                                            <path d="M4 4l8 8M12 4l-8 8" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"></path>
                                                        </svg>
                                                    </button>
                                                </form>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            @endforeach
        </div>
        <button type="button" class="side-arrow w-full border-t border-line bg-soft" data-scroll-down hidden aria-label="Orders below">
            <svg viewBox="0 0 16 16" class="h-4 w-4" aria-hidden="true">
                <path d="M3.5 5.5 8 10l4.5-4.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path>
            </svg>
        </button>
    </section>
    </div>

    <aside class="flex min-h-0 w-full shrink-0 flex-1 flex-col overflow-hidden rounded-2xl border border-line bg-card xl:w-72 xl:flex-none" aria-label="Side tools" data-arrow-pane data-pane="tools" data-pane-title="Tools">
        <div class="flex shrink-0 items-center justify-between gap-2 border-b border-line px-3 py-1">
            <h2 class="text-xs font-semibold">Tools</h2>
            @include('forex.partials.pane-controls', ['title' => 'Tools'])
        </div>
        <button type="button" class="side-arrow w-full border-b border-line bg-soft" data-scroll-up hidden aria-label="Tools above">
            <svg viewBox="0 0 16 16" class="h-4 w-4" aria-hidden="true">
                <path d="M3.5 10.5 8 6l4.5 4.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path>
            </svg>
        </button>
        <div class="arrow-scroll min-h-0 flex-1" data-arrow-scroll>
            <form method="POST" action="{{ route('forex.orders.pending') }}" class="grid gap-2 border-b border-line px-3 py-2">
                @csrf
                <h2 class="text-xs font-semibold">New pending order</h2>
                <label class="grid gap-1 text-[11px] text-ink/70">
                    Symbol
                    <select name="symbol" class="h-7 rounded-lg border border-line bg-canvas px-2 text-xs text-ink">
                        @foreach ($quotes as $quote)
                            <option value="{{ $quote['symbol'] }}">{{ $quote['symbol'] }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="grid gap-1 text-[11px] text-ink/70">
                    Type
                    <select name="order_type" class="h-7 rounded-lg border border-line bg-canvas px-2 text-xs text-ink">
                        <option value="buy_limit">Buy Limit</option>
                        <option value="sell_limit">Sell Limit</option>
                        <option value="buy_stop">Buy Stop</option>
                        <option value="sell_stop">Sell Stop</option>
                    </select>
                </label>
                <label class="grid gap-1 text-[11px] text-ink/70">
                    Lots
                    <input type="number" name="quantity" value="0.01" min="0.01" step="0.01" class="h-7 rounded-lg border border-line bg-canvas px-2 text-xs tabular-nums text-ink">
                </label>
                <label class="grid gap-1 text-[11px] text-ink/70">
                    Price
                    <input type="number" name="trigger_price" value="" min="0" step="any" required class="h-7 rounded-lg border border-line bg-canvas px-2 text-xs tabular-nums text-ink">
                </label>
                <div class="grid grid-cols-2 gap-2">
                    <label class="grid gap-1 text-[11px] text-ink/70">
                        S/L
                        <input type="number" name="stop_loss" value="" min="0" step="any" class="h-7 rounded-lg border border-line bg-canvas px-2 text-xs tabular-nums text-ink">
                    </label>
                    <label class="grid gap-1 text-[11px] text-ink/70">
                        T/P
                        <input type="number" name="take_profit" value="" min="0" step="any" class="h-7 rounded-lg border border-line bg-canvas px-2 text-xs tabular-nums text-ink">
                    </label>
                </div>
                <button type="submit" class="h-7 rounded-lg bg-ink px-3 text-xs font-semibold text-canvas">Place pending</button>
            </form>

            <form id="lot-calculator" class="grid gap-2 border-t border-line px-3 py-2" data-balance="{{ $balance }}">
                <h2 class="text-xs font-semibold">Lot calculator</h2>
                <label class="grid gap-1 text-[11px] text-ink/70">
                    Symbol
                    <select name="symbol" class="h-7 rounded-lg border border-line bg-canvas px-2 text-xs text-ink">
                        @foreach ($quotes as $quote)
                            <option value="{{ $quote['symbol'] }}" data-bid="{{ $quote['bid'] }}">{{ $quote['symbol'] }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="grid gap-1 text-[11px] text-ink/70">
                    Risk %
                    <input type="number" name="risk" value="1" min="0.1" step="0.1" class="h-7 rounded-lg border border-line bg-canvas px-2 text-xs tabular-nums text-ink">
                </label>
                <label class="grid gap-1 text-[11px] text-ink/70">
                    Stop, pips
                    <input type="number" name="stop" value="20" min="0.1" step="0.1" class="h-7 rounded-lg border border-line bg-canvas px-2 text-xs tabular-nums text-ink">
                </label>
                <div class="flex items-center justify-between text-xs">
                    <span>Lots</span>
                    <span id="lot-result" class="font-semibold tabular-nums">0.50</span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <span>Pip value</span>
                    <span id="pip-value" class="font-semibold tabular-nums">10.00</span>
                </div>
            </form>
        </div>
        <button type="button" class="side-arrow w-full border-t border-line bg-soft" data-scroll-down hidden aria-label="Tools below">
            <svg viewBox="0 0 16 16" class="h-4 w-4" aria-hidden="true">
                <path d="M3.5 5.5 8 10l4.5-4.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path>
            </svg>
        </button>
    </aside>
    </div>

    @push('windows')
        <x-desk-window id="watchlist-window" title="My Wishlist" :open="$errors->any() && old('_form') === 'watchlist'">
            <form id="wishlist-form" method="POST" action="{{ route('forex.market-watch.watchlist') }}" class="flex h-full flex-col gap-4 p-4 sm:gap-5 sm:p-6" data-max="{{ \Modules\Forex\Support\Symbols::max() }}">
                @csrf
                <input type="hidden" name="_form" value="watchlist">
                <p class="text-sm text-ink/70">
                    Your list is saved per login. Order is kept. Remove or move rows anytime (max {{ \Modules\Forex\Support\Symbols::max() }}).
                </p>

                <div class="grid gap-2">
                    <h3 class="text-xs font-semibold tracking-wide uppercase text-ink/60">On your wishlist</h3>
                    <ul id="wishlist-ordered" class="grid gap-1.5" data-empty-label="No symbols yet — add from below.">
                        @forelse ($watchlist as $symbol)
                            <li class="flex items-center gap-2 rounded-xl border border-line bg-canvas px-2 py-1.5" data-symbol="{{ $symbol }}">
                                <input type="hidden" name="symbols[]" value="{{ $symbol }}">
                                <span class="min-w-0 flex-1 truncate text-sm font-semibold">{{ $symbol }}</span>
                                <button type="button" data-wl-up class="grid h-7 w-7 place-items-center rounded-lg hover:bg-soft" aria-label="Move {{ $symbol }} up">↑</button>
                                <button type="button" data-wl-down class="grid h-7 w-7 place-items-center rounded-lg hover:bg-soft" aria-label="Move {{ $symbol }} down">↓</button>
                                <button type="button" data-wl-remove class="grid h-7 w-7 place-items-center rounded-lg hover:bg-soft" aria-label="Remove {{ $symbol }}">✕</button>
                            </li>
                        @empty
                            <li data-wl-empty class="rounded-xl border border-dashed border-line px-3 py-3 text-xs text-ink/60">No symbols yet — add from below.</li>
                        @endforelse
                    </ul>
                </div>

                @foreach ($categories as $category)
                    @php $group = $groupedSymbols[$category['key']] ?? []; @endphp
                    @if ($group !== [])
                        <div class="grid gap-2">
                            <h3 class="text-xs font-semibold tracking-wide uppercase text-ink/60">Add · {{ $category['label'] }}</h3>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($group as $symbol)
                                    @php
                                        $isLive = in_array($symbol, $liveSymbols, true);
                                        $brokerClosed = $feedOnline && ! $isLive;
                                    @endphp
                                    <button
                                        type="button"
                                        data-wl-add="{{ $symbol }}"
                                        @disabled(in_array($symbol, $watchlist, true))
                                        @class([
                                            'rounded-lg border px-2.5 py-1.5 text-xs font-semibold',
                                            'border-line bg-canvas hover:bg-soft disabled:opacity-40' => ! $brokerClosed,
                                            'border-red-300 bg-red-50 text-red-900' => $brokerClosed,
                                        ])
                                        title="{{ $brokerClosed ? 'Broker closed — you cannot get rates on this symbol.' : ($isLive ? 'Live' : 'Add to wishlist') }}"
                                    >{{ $symbol }}{{ $brokerClosed ? ' · closed' : '' }}</button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach

                <label class="flex flex-col gap-2 text-sm font-semibold">
                    Add symbol
                    <input
                        type="text"
                        name="custom"
                        value="{{ old('custom') }}"
                        maxlength="32"
                        autocomplete="off"
                        placeholder="Gate symbol only, e.g. BTCUSD"
                        class="rounded-xl border border-line bg-canvas px-4 py-3 text-base font-normal outline-none focus:border-ink"
                    >
                </label>
                @error('custom')
                    <p class="text-sm text-red-800">{{ $message }}</p>
                @enderror
                <div class="mt-auto flex flex-wrap justify-end gap-2 border-t border-line pt-4">
                    <button type="button" data-window-close class="rounded-xl border border-line bg-canvas px-4 py-2 text-sm font-semibold">Cancel</button>
                    <button type="submit" class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold">Save wishlist</button>
                </div>
            </form>
        </x-desk-window>
    @endpush

    <script>
        const fastArms = () => document.querySelectorAll('[data-fast-arm]');
        const fastMaster = document.getElementById('fast-trade-master');
        let symbolSession = 'wishlist';

        const applySymbolSession = (session) => {
            symbolSession = session;

            document.querySelectorAll('[data-symbol-session]').forEach((tab) => {
                const on = tab.dataset.symbolSession === session;
                tab.setAttribute('aria-selected', on ? 'true' : 'false');
                tab.classList.toggle('bg-primary', on);
                tab.classList.toggle('font-semibold', true);
                tab.classList.toggle('hover:bg-soft', ! on);
            });

            document.querySelectorAll('[data-quote-row]').forEach((row) => {
                const show = session === 'wishlist'
                    ? row.dataset.inWishlist === '1'
                    : row.dataset.category === session;

                row.hidden = ! show;
            });

            window.refreshDeskArrowPanes?.();
        };

        document.querySelectorAll('[data-symbol-session]').forEach((tab) => {
            tab.addEventListener('click', () => {
                applySymbolSession(tab.dataset.symbolSession);
            });
        });

        const FAST_KEY = 'desk-fast-trading';
        const fastHint = document.getElementById('fast-trade-hint');

        const syncFastTrade = (box) => {
            box.closest('form').querySelectorAll('[data-fast-trade]').forEach((button) => {
                button.disabled = ! box.checked;
            });
        };

        const applyFastMode = (on, store = true) => {
            fastMaster.checked = on;
            fastMaster.indeterminate = false;
            fastHint?.classList.toggle('hidden', ! on);

            if (store) {
                localStorage.setItem(FAST_KEY, on ? '1' : '0');
            }

            fastArms().forEach((box) => {
                box.checked = on;
                syncFastTrade(box);
            });
        };

        fastMaster.addEventListener('change', () => {
            applyFastMode(fastMaster.checked);
        });

        fastArms().forEach((box) => {
            box.addEventListener('change', () => {
                syncFastTrade(box);

                if (! fastMaster.checked) {
                    return;
                }

                const boxes = [...fastArms()];
                const armed = boxes.filter((item) => item.checked).length;

                if (armed === 0) {
                    applyFastMode(false);
                } else if (armed < boxes.length) {
                    fastMaster.indeterminate = true;
                } else {
                    fastMaster.indeterminate = false;
                }
            });
        });

        applyFastMode(localStorage.getItem(FAST_KEY) === '1', false);

        const wishlistForm = document.getElementById('wishlist-form');
        const wishlistList = document.getElementById('wishlist-ordered');

        const wishlistSymbols = () => [...wishlistList.querySelectorAll('[data-symbol]')].map((row) => row.dataset.symbol);

        const refreshWishlistAddButtons = () => {
            const selected = new Set(wishlistSymbols());

            document.querySelectorAll('[data-wl-add]').forEach((button) => {
                button.disabled = selected.has(button.dataset.wlAdd);
            });
        };

        const syncWishlistEmpty = () => {
            const empty = wishlistList.querySelector('[data-wl-empty]');
            const rows = wishlistList.querySelectorAll('[data-symbol]');

            if (rows.length === 0 && ! empty) {
                const item = document.createElement('li');
                item.dataset.wlEmpty = '1';
                item.className = 'rounded-xl border border-dashed border-line px-3 py-3 text-xs text-ink/60';
                item.textContent = wishlistList.dataset.emptyLabel || 'No symbols yet — add from below.';
                wishlistList.appendChild(item);
            }

            if (rows.length > 0) {
                empty?.remove();
            }
        };

        const bindWishlistRow = (row) => {
            row.querySelector('[data-wl-up]')?.addEventListener('click', () => {
                row.previousElementSibling?.before(row);
            });
            row.querySelector('[data-wl-down]')?.addEventListener('click', () => {
                row.nextElementSibling?.after(row);
            });
            row.querySelector('[data-wl-remove]')?.addEventListener('click', () => {
                row.remove();
                syncWishlistEmpty();
                refreshWishlistAddButtons();
            });
        };

        const addWishlistSymbol = (symbol) => {
            const max = Number(wishlistForm?.dataset.max || 15);
            const key = String(symbol || '').toUpperCase().trim();

            if (! key || wishlistSymbols().includes(key) || wishlistSymbols().length >= max) {
                return;
            }

            wishlistList.querySelector('[data-wl-empty]')?.remove();

            const row = document.createElement('li');
            row.className = 'flex items-center gap-2 rounded-xl border border-line bg-canvas px-2 py-1.5';
            row.dataset.symbol = key;
            row.innerHTML = `
                <input type="hidden" name="symbols[]" value="${key}">
                <span class="min-w-0 flex-1 truncate text-sm font-semibold">${key}</span>
                <button type="button" data-wl-up class="grid h-7 w-7 place-items-center rounded-lg hover:bg-soft" aria-label="Move ${key} up">↑</button>
                <button type="button" data-wl-down class="grid h-7 w-7 place-items-center rounded-lg hover:bg-soft" aria-label="Move ${key} down">↓</button>
                <button type="button" data-wl-remove class="grid h-7 w-7 place-items-center rounded-lg hover:bg-soft" aria-label="Remove ${key}">✕</button>
            `;
            wishlistList.appendChild(row);
            bindWishlistRow(row);
            refreshWishlistAddButtons();
        };

        wishlistList?.querySelectorAll('[data-symbol]').forEach((row) => bindWishlistRow(row));
        document.querySelectorAll('[data-wl-add]').forEach((button) => {
            button.addEventListener('click', () => addWishlistSymbol(button.dataset.wlAdd));
        });
        refreshWishlistAddButtons();

        document.querySelectorAll('[data-order-tab]').forEach((tab) => {
            tab.addEventListener('click', () => {
                const name = tab.dataset.orderTab;

                document.querySelectorAll('[data-order-tab]').forEach((item) => {
                    item.setAttribute('aria-selected', item === tab ? 'true' : 'false');
                });
                document.querySelectorAll('[data-order-panel]').forEach((panel) => {
                    panel.hidden = panel.dataset.orderPanel !== name;
                });
                window.refreshDeskArrowPanes?.();
            });
        });

        const lotForm = document.getElementById('lot-calculator');
        const lotSpecs = {
            EURUSD: { pip: 0.0001, contract: 100000 },
            GBPUSD: { pip: 0.0001, contract: 100000 },
            AUDUSD: { pip: 0.0001, contract: 100000 },
            USDJPY: { pip: 0.01, contract: 100000, divide: true },
            USDCHF: { pip: 0.0001, contract: 100000, divide: true },
            XAUUSD: { pip: 0.1, contract: 100 },
            XAGUSD: { pip: 0.001, contract: 5000 },
            US30: { pip: 1, contract: 1 },
            BTCUSD: { pip: 1, contract: 1 },
        };

        const updateLots = () => {
            const option = lotForm.symbol.selectedOptions[0];
            const spec = lotSpecs[option.value];
            const bid = Number(option.dataset.bid);
            const raw = spec.pip * spec.contract;
            const pip = spec.divide ? raw / bid : raw;
            const risk = Number(lotForm.risk.value);
            const stop = Number(lotForm.stop.value);
            const balance = Number(lotForm.dataset.balance);
            const lots = stop > 0 && pip > 0 ? (balance * (risk / 100)) / (stop * pip) : 0;

            document.getElementById('pip-value').textContent = pip.toFixed(2);
            document.getElementById('lot-result').textContent = lots.toFixed(2);
        };

        lotForm.addEventListener('input', updateLots);
        lotForm.addEventListener('submit', (event) => {
            event.preventDefault();
            updateLots();
        });
        updateLots();

        const watchBoard = document.getElementById('watch-board');
        const watchMain = document.getElementById('watch-main');
        const watchDock = () => document.getElementById('desk-dock');
        const panes = [...watchBoard.querySelectorAll('[data-pane]')];

        const paneChip = (pane) => {
            let chip = watchDock().querySelector(`[data-dock="${pane.dataset.pane}"]`);

            if (chip) {
                return chip;
            }

            chip = document.createElement('button');
            chip.type = 'button';
            chip.dataset.dock = pane.dataset.pane;
            chip.className = 'shrink-0 rounded-xl bg-primary px-3 py-1 text-sm font-semibold';
            chip.textContent = pane.dataset.paneTitle;
            chip.addEventListener('click', () => restorePane(pane));
            watchDock().appendChild(chip);

            return chip;
        };

        const syncPane = (pane) => {
            const maxButton = pane.querySelector('[data-pane-max]');
            const maxed = pane.classList.contains('is-max');
            const maxIcon = maxButton.querySelector('[data-pane-max-icon]');
            const restoreIcon = maxButton.querySelector('[data-pane-restore-icon]');

            maxIcon.hidden = maxed;
            restoreIcon.hidden = ! maxed;
            maxButton.setAttribute('aria-label', `${maxed ? 'Restore' : 'Maximize'} ${pane.dataset.paneTitle}`);
        };

        const applyPanes = () => {
            const maxed = panes.find((pane) => pane.classList.contains('is-max'));

            panes.forEach((pane) => {
                pane.hidden = maxed ? pane !== maxed : pane.classList.contains('is-min');
                syncPane(pane);

                if (pane.classList.contains('is-min') || pane.classList.contains('is-max')) {
                    paneChip(pane);
                } else {
                    watchDock().querySelector(`[data-dock="${pane.dataset.pane}"]`)?.remove();
                }
            });

            watchMain.hidden = [...watchMain.querySelectorAll('[data-pane]')].every((pane) => pane.hidden);
            window.refreshDeskArrowPanes?.();
        };

        const restorePane = (pane) => {
            pane.classList.remove('is-max', 'is-min');
            applyPanes();
        };

        panes.forEach((pane) => {
            pane.querySelector('[data-pane-min]').addEventListener('click', () => {
                pane.classList.remove('is-max');
                pane.classList.add('is-min');
                applyPanes();
            });
            pane.querySelector('[data-pane-max]').addEventListener('click', () => {
                if (pane.classList.contains('is-max')) {
                    restorePane(pane);

                    return;
                }

                panes.forEach((other) => other.classList.remove('is-max'));
                pane.classList.remove('is-min');
                pane.classList.add('is-max');
                applyPanes();
            });
        });

        const sessionOpen = (minute, open, close) => open < close
            ? minute >= open && minute < close
            : minute >= open || minute < close;

        const liquidityFor = (active) => {
            if (active.includes('london') && active.includes('new-york')) {
                return { level: 'high', percent: 100, color: '#15803d' };
            }

            if (active.length >= 2) {
                return { level: 'medium', percent: 62, color: '#ea580c' };
            }

            if (active.length === 1) {
                return { level: 'low', percent: 32, color: '#dc2626' };
            }

            return { level: 'none', percent: 8, color: '#a8a29e' };
        };

        const syncSessions = () => {
            const now = new Date();
            const minute = now.getUTCHours() * 60 + now.getUTCMinutes();
            const active = [];

            document.querySelectorAll('[data-session]').forEach((chip) => {
                const on = sessionOpen(minute, Number(chip.dataset.open), Number(chip.dataset.close));

                chip.classList.toggle('bg-emerald-700', on);
                chip.classList.toggle('text-white', on);
                chip.classList.toggle('bg-white', ! on);
                chip.classList.toggle('text-ink', ! on);
                chip.classList.toggle('border', ! on);
                chip.classList.toggle('border-line', ! on);

                if (on) {
                    active.push(chip.dataset.session);
                }
            });

            const liquidity = liquidityFor(active);
            const fill = document.querySelector('[data-liquidity-fill]');
            const track = document.querySelector('[data-liquidity-track]');

            if (fill) {
                fill.style.width = `${liquidity.percent}%`;
                fill.style.backgroundColor = liquidity.color;
            }

            if (track) {
                track.setAttribute('aria-label', `Liquidity ${liquidity.level}`);
            }
        };

        syncSessions();
        setInterval(syncSessions, 30000);

        const quotesUrl = @json(route('forex.market-watch.quotes'));
        const bridgeQuotesUrl = 'http://127.0.0.1:8766/quotes';
        let ticksInFlight = false;
        let bookInFlight = false;
        let bridgeLive = true;
        let lastDeskPull = 0;

        const paintMeters = (payload) => {
            (payload.account || []).forEach((meter) => {
                const box = document.querySelector(`[data-meter="${meter.label}"] [data-meter-value]`);

                if (! box) {
                    return;
                }

                box.textContent = meter.value;
                box.classList.toggle('text-emerald-800', meter.tone === 'profit');
                box.classList.toggle('text-red-800', meter.tone === 'loss');
            });
        };

        const paintTicks = (payload, prices = true) => {
            (payload.quotes || []).forEach((quote) => {
                const row = document.querySelector(`[data-quote-row="${quote.symbol}"]`);

                if (! row) {
                    return;
                }

                row.dataset.live = quote.live ? '1' : '0';
                row.dataset.brokerOpen = quote.broker_open ? '1' : '0';

                if (prices) {
                    ['bid', 'ask', 'spread', 'low', 'range', 'high', 'time'].forEach((key) => {
                        const cell = row.querySelector(`[data-q="${key}"]`);

                        if (cell && quote[key] !== undefined) {
                            cell.textContent = quote[key];
                        }
                    });
                }

                let note = row.querySelector('[data-q="broker-note"]');

                if (quote.broker_open === false) {
                    if (! note) {
                        note = document.createElement('span');
                        note.dataset.q = 'broker-note';
                        note.className = 'mt-0.5 block text-[10px] font-medium text-red-800';
                        row.querySelector('td')?.appendChild(note);
                    }

                    note.textContent = 'Broker closed — no rates';
                } else if (note) {
                    note.remove();
                }

                if (quote.profit !== undefined) {
                    const profitCell = row.querySelector('[data-q="profit"]');

                    if (profitCell) {
                        profitCell.textContent = quote.profit;
                        profitCell.classList.toggle('text-emerald-800', String(quote.profit || '').startsWith('+'));
                        profitCell.classList.toggle('text-red-800', String(quote.profit || '').startsWith('-'));
                    }
                }

                if (quote.buy_lots !== undefined) {
                    const buyLots = row.querySelector('[data-q="buy-lots"]');
                    const sellLots = row.querySelector('[data-q="sell-lots"]');

                    if (buyLots) {
                        buyLots.textContent = `Buy = ${quote.buy_lots}`;
                    }

                    if (sellLots) {
                        sellLots.textContent = `Sell = ${quote.sell_lots}`;
                    }
                }

                if (quote.profit_count !== undefined) {
                    const setClose = (key, enabled) => {
                        const button = row.querySelector(`[data-close="${key}"] button`);

                        if (button) {
                            button.disabled = ! enabled;
                        }
                    };

                    setClose('profit', Number(quote.profit_count) > 0);
                    setClose('loss', Number(quote.loss_count) > 0);
                    setClose('all', Number(quote.open_count) > 0);
                }
            });

            paintMeters(payload);
        };

        const paintBook = (payload) => {
            if (payload.watchlist) {
                const wishlist = new Set(payload.watchlist);

                document.querySelectorAll('[data-quote-row]').forEach((row) => {
                    row.dataset.inWishlist = wishlist.has(row.dataset.quoteRow) ? '1' : '0';
                });

                applySymbolSession(symbolSession);
            }

            paintTicks(payload, ! bridgeLive);
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

        const refreshBook = async () => {
            if (bookInFlight) {
                return;
            }

            bookInFlight = true;

            try {
                const response = await fetch(quotesUrl, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });

                if (! response.ok) {
                    return;
                }

                paintBook(await response.json());
            } catch (error) {
                // Keep the last painted book if the poll fails.
            } finally {
                bookInFlight = false;
            }
        };

        setInterval(refreshTicks, 20);
        setInterval(() => {
            bridgeLive = true;
        }, 2000);
        setInterval(refreshBook, 1000);
        refreshTicks();
    </script>
</x-layouts.shell>
