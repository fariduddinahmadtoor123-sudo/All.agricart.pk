<?php

namespace Modules\Forex\Support;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Modules\Forex\Models\Mt5Account;
use Modules\Forex\Models\Mt5Quote;
use Modules\Forex\Models\Position;

class MarketWatch
{
    public const BALANCE = 10000.0;

    /**
     * @return list<string>
     */
    public static function symbols(?User $user = null): array
    {
        return array_column(self::quotes($user), 'symbol');
    }

    /**
     * @param  list<string>|null  $symbols
     * @return list<array{symbol: string, bid: string, ask: string, spread: string, high: string, range: string, low: string, time: string, category: string|null, live: bool, broker_open: bool, profit: string, profit_count: int, loss_count: int, open_count: int, buy_lots: string, sell_lots: string}>
     */
    public static function rows(User $user, ?array $symbols = null, bool $markToMarket = true): array
    {
        if ($markToMarket) {
            (new Trading)->markOpen($user);
        }

        /** @var Collection<string, Collection<int, Position>> $open */
        $open = Position::query()
            ->where('user_id', $user->id)
            ->where('status', Position::STATUS_OPEN)
            ->whereNull('closed_at')
            ->get(['symbol', 'side', 'quantity', 'profit', 'entry_price'])
            ->groupBy('symbol');

        return array_map(function (array $quote) use ($open): array {
            $trades = $open->get($quote['symbol'], collect());
            $profit = $trades->sum(fn (Position $trade): float => (float) $trade->profit);

            return [
                ...$quote,
                'profit' => self::money($profit),
                'profit_count' => $trades->filter(fn (Position $trade): bool => (float) $trade->profit > 0)->count(),
                'loss_count' => $trades->filter(fn (Position $trade): bool => (float) $trade->profit < 0)->count(),
                'open_count' => $trades->count(),
                'buy_lots' => self::lots($trades, 'buy'),
                'sell_lots' => self::lots($trades, 'sell'),
            ];
        }, self::quotes($user, $symbols));
    }

    /**
     * Full gate book for category tabs (wishlist is a filter over this).
     *
     * @return list<array{symbol: string, bid: string, ask: string, spread: string, high: string, range: string, low: string, time: string, category: string|null, live: bool, broker_open: bool, profit: string, profit_count: int, loss_count: int, open_count: int, buy_lots: string, sell_lots: string}>
     */
    public static function gateRows(User $user, bool $markToMarket = true): array
    {
        return self::rows($user, Symbols::allowed(), $markToMarket);
    }

    /**
     * Tick paint only — no P/L rewrite (keeps the terminal tight to MT5).
     *
     * @return list<array{symbol: string, bid: string, ask: string, spread: string, high: string, range: string, low: string, time: string, category: string|null, live: bool, broker_open: bool}>
     */
    public static function gateTicks(?User $user = null): array
    {
        return self::quotes($user, Symbols::allowed());
    }

    /**
     * @param  list<string>|null  $symbols
     * @return list<array{symbol: string, bid: string, ask: string, spread: string, high: string, range: string, low: string, time: string, category: string|null, live: bool, broker_open: bool}>
     */
    public static function quotes(?User $user = null, ?array $symbols = null): array
    {
        $wanted = $symbols
            ?? ($user !== null ? Watchlist::for($user) : Watchlist::defaults());
        $wanted = Symbols::onlyAllowed($wanted);
        $live = self::liveQuotes($user);
        $feedOnline = self::onlineFeed($user) !== null;

        return array_map(function (string $symbol) use ($live, $feedOnline): array {
            $symbol = strtoupper($symbol);
            $matched = self::matchLiveQuote($live, $symbol);
            $category = Symbols::categoryFor($symbol);

            if ($matched !== null) {
                return [
                    ...$matched,
                    'category' => $category,
                    'live' => true,
                    'broker_open' => true,
                ];
            }

            return [
                'symbol' => $symbol,
                'bid' => '—',
                'ask' => '—',
                'spread' => '—',
                'high' => '—',
                'range' => '—',
                'low' => '—',
                'time' => '—',
                'category' => $category,
                'live' => false,
                'broker_open' => ! $feedOnline,
            ];
        }, $wanted);
    }

    public static function onlineFeed(?User $user = null): ?Mt5Account
    {
        $fresh = now()->subSeconds(30);
        $feed = null;

        $linkedId = $user?->clientAccount?->mt5_account_id;

        if ($linkedId) {
            $feed = Mt5Account::query()
                ->whereKey($linkedId)
                ->where('active', true)
                ->whereNotNull('last_seen_at')
                ->where('last_seen_at', '>=', $fresh)
                ->first();
        }

        return $feed ?? Mt5Account::query()
            ->where('active', true)
            ->whereNotNull('last_seen_at')
            ->where('last_seen_at', '>=', $fresh)
            ->latest('last_seen_at')
            ->first();
    }

    /**
     * @param  array<string, array{symbol: string, bid: string, ask: string, spread: string, high: string, range: string, low: string, time: string}>  $live
     * @return array{symbol: string, bid: string, ask: string, spread: string, high: string, range: string, low: string, time: string}|null
     */
    private static function matchLiveQuote(array $live, string $wanted): ?array
    {
        $wanted = strtoupper($wanted);

        if (isset($live[$wanted])) {
            return [...$live[$wanted], 'symbol' => $wanted];
        }

        foreach ($live as $symbol => $row) {
            $symbol = strtoupper((string) $symbol);

            if (str_starts_with($symbol, $wanted) || str_starts_with($wanted, $symbol)) {
                return [...$row, 'symbol' => $wanted];
            }
        }

        return null;
    }

    /**
     * @return array<string, array{symbol: string, bid: string, ask: string, spread: string, high: string, range: string, low: string, time: string}>
     */
    private static function liveQuotes(?User $user = null): array
    {
        $feed = self::onlineFeed($user);

        if ($feed === null) {
            return [];
        }

        $allowed = array_flip(Symbols::allowed());

        return $feed->quotes()
            ->get()
            ->filter(fn (Mt5Quote $quote): bool => isset($allowed[strtoupper($quote->symbol)]))
            ->mapWithKeys(function (Mt5Quote $quote): array {
                $bid = (float) $quote->bid;
                $ask = (float) $quote->ask;
                $high = (float) ($quote->high ?? $ask);
                $low = (float) ($quote->low ?? $bid);
                $range = max(0, $high - $low);
                $spreadPoints = $ask - $bid;
                $digits = self::digitsFor($quote);

                return [
                    strtoupper($quote->symbol) => [
                        'symbol' => strtoupper($quote->symbol),
                        'bid' => self::price($bid, $digits),
                        'ask' => self::price($ask, $digits),
                        'spread' => self::spreadLabel($spreadPoints, $quote->symbol, $digits),
                        'high' => self::price($high, $digits),
                        'range' => self::price($range, $digits),
                        'low' => self::price($low, $digits),
                        'time' => ($quote->tick_at ?? now())->timezone(config('app.timezone'))->format('H:i:s'),
                    ],
                ];
            })
            ->all();
    }

    private static function digitsFor(Mt5Quote $quote): int
    {
        if ($quote->digits !== null) {
            return max(0, min(8, (int) $quote->digits));
        }

        $key = strtoupper($quote->symbol);

        return match (true) {
            str_starts_with($key, 'USDJPY'), str_starts_with($key, 'EURJPY'), str_starts_with($key, 'GBPJPY') => 3,
            str_starts_with($key, 'XAU'), str_starts_with($key, 'XAG'), str_starts_with($key, 'NAS') => 2,
            str_starts_with($key, 'US30'), str_starts_with($key, 'BTC') => 2,
            default => 5,
        };
    }

    private static function price(float $value, int $digits): string
    {
        return number_format($value, $digits, '.', '');
    }

    private static function spreadLabel(float $spread, string $symbol, int $digits): string
    {
        $key = strtoupper($symbol);

        if (
            str_starts_with($key, 'XAU')
            || str_starts_with($key, 'XAG')
            || str_starts_with($key, 'US30')
            || str_starts_with($key, 'NAS')
            || str_starts_with($key, 'BTC')
        ) {
            return rtrim(rtrim(number_format($spread, max(2, $digits), '.', ''), '0'), '.') ?: '0';
        }

        $points = $spread * (str_contains($key, 'JPY') ? 100 : 10000);

        return number_format($points, 1, '.', '');
    }

    /**
     * @return array{pending: list<array<string, mixed>>, open: list<array<string, mixed>>, history: list<array<string, mixed>>}
     */
    public static function book(User $user): array
    {
        (new Trading)->markOpen($user);

        $positions = Position::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->get();

        $present = function (Position $position, string $when): array {
            $price = static function (?float $value): string {
                if ($value === null || $value <= 0) {
                    return '—';
                }

                return rtrim(rtrim(number_format($value, 5, '.', ''), '0'), '.') ?: '0';
            };

            $type = match ($position->order_type) {
                'buy_limit' => 'Buy Limit',
                'sell_limit' => 'Sell Limit',
                'buy_stop' => 'Buy Stop',
                'sell_stop' => 'Sell Stop',
                default => 'Market',
            };

            $historyLabel = $position->status === Position::STATUS_CANCELLED ? 'Cancelled' : 'Closed';

            return [
                'id' => $position->id,
                'ticket' => $position->desk_ticket ?: ('D-'.$position->id),
                'symbol' => $position->symbol,
                'type' => $type,
                'side' => $position->side === null ? '—' : ucfirst($position->side),
                'quantity' => $position->quantity === null ? '—' : number_format((float) $position->quantity, 2, '.', ''),
                'entry' => $price($position->entry_price !== null ? (float) $position->entry_price : null),
                'trigger' => $price($position->trigger_price !== null ? (float) $position->trigger_price : null),
                'close' => $price($position->close_price !== null ? (float) $position->close_price : null),
                'stop_loss' => $price($position->stop_loss !== null ? (float) $position->stop_loss : null),
                'stop_loss_raw' => $position->stop_loss !== null ? (float) $position->stop_loss : '',
                'take_profit' => $price($position->take_profit !== null ? (float) $position->take_profit : null),
                'take_profit_raw' => $position->take_profit !== null ? (float) $position->take_profit : '',
                'commission' => number_format((float) ($position->commission ?? 0), 2, '.', ''),
                'profit' => self::money((float) $position->profit),
                'result' => $historyLabel,
                'time' => optional($position->{$when})?->timezone(config('app.timezone'))->format('d M H:i:s') ?? '—',
                'can_close' => $position->status === Position::STATUS_OPEN && $position->closed_at === null,
                'can_cancel' => $position->status === Position::STATUS_PENDING && $position->closed_at === null,
                'can_edit_levels' => in_array($position->status, [Position::STATUS_OPEN, Position::STATUS_PENDING], true)
                    && $position->closed_at === null,
            ];
        };

        return [
            'pending' => $positions
                ->where('status', Position::STATUS_PENDING)
                ->whereNull('closed_at')
                ->map(fn (Position $position): array => $present($position, 'created_at'))
                ->values()
                ->all(),
            'open' => $positions
                ->where('status', Position::STATUS_OPEN)
                ->whereNull('closed_at')
                ->map(fn (Position $position): array => $present($position, 'created_at'))
                ->values()
                ->all(),
            'history' => $positions
                ->filter(fn (Position $position): bool => in_array($position->status, [Position::STATUS_CLOSED, Position::STATUS_CANCELLED], true)
                    || $position->closed_at !== null)
                ->map(fn (Position $position): array => $present($position, 'closed_at'))
                ->values()
                ->all(),
        ];
    }

    /**
     * Desk ledger meters only — never the linked MT5 account snapshot.
     *
     * @return list<array{label: string, value: string, tone: string}>
     */
    public static function account(User $user): array
    {
        $deskProfit = (float) Position::query()
            ->where('user_id', $user->id)
            ->where('status', Position::STATUS_OPEN)
            ->whereNull('closed_at')
            ->sum('profit');

        $balance = self::balanceFor($user);
        $equity = $balance + $deskProfit;

        return [
            ['label' => 'Balance', 'value' => self::figure($balance), 'tone' => ''],
            ['label' => 'Equity', 'value' => self::figure($equity), 'tone' => ''],
            ['label' => 'P/L', 'value' => self::money($deskProfit), 'tone' => $deskProfit > 0 ? 'profit' : ($deskProfit < 0 ? 'loss' : '')],
            ['label' => 'Margin', 'value' => self::figure(0.0), 'tone' => ''],
            ['label' => 'Free margin', 'value' => self::figure($equity), 'tone' => ''],
        ];
    }

    public static function balanceFor(User $user): float
    {
        $account = $user->clientAccount;

        if ($account !== null) {
            return (float) $account->balance;
        }

        return self::BALANCE;
    }

    /**
     * Major forex sessions in the order they open, using fixed UTC hours.
     *
     * @return list<array{key: string, label: string, open: int, close: int, active: bool}>
     */
    public static function sessions(?CarbonInterface $at = null): array
    {
        $at ??= now('UTC');
        $minute = ((int) $at->format('G')) * 60 + (int) $at->format('i');

        return array_map(fn (array $session): array => [
            ...$session,
            'active' => self::sessionIsOpen($minute, $session['open'], $session['close']),
        ], [
            ['key' => 'sydney', 'label' => 'Sydney', 'open' => 22 * 60, 'close' => 7 * 60],
            ['key' => 'tokyo', 'label' => 'Tokyo', 'open' => 0, 'close' => 9 * 60],
            ['key' => 'london', 'label' => 'London', 'open' => 8 * 60, 'close' => 17 * 60],
            ['key' => 'new-york', 'label' => 'New York', 'open' => 13 * 60, 'close' => 22 * 60],
        ]);
    }

    /**
     * @return array{level: string, percent: int, color: string}
     */
    public static function liquidity(?CarbonInterface $at = null): array
    {
        $active = collect(self::sessions($at))
            ->filter(fn (array $session): bool => $session['active'])
            ->pluck('key')
            ->all();

        if (in_array('london', $active, true) && in_array('new-york', $active, true)) {
            return ['level' => 'high', 'percent' => 100, 'color' => '#15803d'];
        }

        if (count($active) >= 2) {
            return ['level' => 'medium', 'percent' => 62, 'color' => '#ea580c'];
        }

        if (count($active) === 1) {
            return ['level' => 'low', 'percent' => 32, 'color' => '#dc2626'];
        }

        return ['level' => 'none', 'percent' => 8, 'color' => '#a8a29e'];
    }

    private static function sessionIsOpen(int $minute, int $open, int $close): bool
    {
        if ($open < $close) {
            return $minute >= $open && $minute < $close;
        }

        return $minute >= $open || $minute < $close;
    }

    /**
     * @param  Collection<int, Position>  $trades
     */
    private static function lots(Collection $trades, string $side): string
    {
        $total = $trades
            ->where('side', $side)
            ->sum(fn (Position $trade): float => (float) $trade->quantity);

        return number_format($total, 2, '.', '');
    }

    public static function figure(float $amount): string
    {
        return number_format($amount, 2, '.', ',');
    }

    public static function money(float $amount): string
    {
        $formatted = number_format($amount, 2, '.', '');

        return $amount > 0 ? '+'.$formatted : $formatted;
    }
}
