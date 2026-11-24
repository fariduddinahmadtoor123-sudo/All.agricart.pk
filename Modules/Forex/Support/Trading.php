<?php

namespace Modules\Forex\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Forex\Models\ClientAccount;
use Modules\Forex\Models\LedgerEntry;
use Modules\Forex\Models\Mt5Command;
use Modules\Forex\Models\Mt5Quote;
use Modules\Forex\Models\Position;

class Trading
{
    public function __construct(
        private Ledger $ledger = new Ledger,
        private Mt5Commands $mt5 = new Mt5Commands,
    ) {}

    /**
     * @return array{bid: float, ask: float}|null
     */
    public function quoteFor(User $user, string $symbol): ?array
    {
        $symbol = strtoupper($symbol);

        foreach (MarketWatch::quotes($user, Symbols::allowed()) as $row) {
            if (strtoupper($row['symbol']) !== $symbol) {
                continue;
            }

            $bid = (float) str_replace(',', '', $row['bid']);
            $ask = (float) str_replace(',', '', $row['ask']);

            if ($bid > 0 && $ask > 0) {
                return ['bid' => $bid, 'ask' => $ask];
            }
        }

        return null;
    }

    /**
     * @param  array{stop_loss?: float|null, take_profit?: float|null, commission?: float|null}  $options
     */
    public function open(User $user, string $symbol, string $side, float $quantity, array $options = []): Position
    {
        $symbol = strtoupper($symbol);
        $side = strtolower($side);

        if (! in_array($side, ['buy', 'sell'], true)) {
            throw new InvalidArgumentException('Side must be buy or sell.');
        }

        if ($quantity < 0.01) {
            throw new InvalidArgumentException('Quantity is too small.');
        }

        $account = $this->terminalAccount($user);
        $this->assertLotSize($account, $symbol, $quantity);
        $quote = $this->quoteFor($user, $symbol);

        if ($quote === null) {
            throw new InvalidArgumentException(
                'This symbol is closed by the broker. You cannot get rates on this symbol.',
            );
        }

        $entry = $side === 'buy' ? $quote['ask'] : $quote['bid'];
        $this->requireFreeMargin($user, $account, $symbol, $quantity, $entry);
        $stopLoss = $this->optionalPrice($options['stop_loss'] ?? null);
        $takeProfit = $this->optionalPrice($options['take_profit'] ?? null);
        $this->assertLevels($side, $quote['bid'], $quote['ask'], $stopLoss, $takeProfit);
        $cuts = $this->lotCuts($account, $quantity);
        $commission = isset($options['commission']) && is_numeric($options['commission'])
            ? round((float) $options['commission'], 2)
            : $cuts['customer'];

        $position = Position::query()->create([
            'desk_ticket' => Position::nextDeskTicket(),
            'mt5_ticket' => null,
            'user_id' => $user->id,
            'client_account_id' => $account->id,
            'symbol' => $symbol,
            'side' => $side,
            'status' => Position::STATUS_OPEN,
            'order_type' => 'market',
            'quantity' => round($quantity, 2),
            'entry_price' => $entry,
            'trigger_price' => null,
            'stop_loss' => $stopLoss,
            'take_profit' => $takeProfit,
            'commission' => $commission,
            'partner_commission' => $cuts['partner'],
            'profit' => 0,
        ]);

        $this->mt5->queue($position, 'open');

        return $position;
    }

    /**
     * @param  array{stop_loss?: float|null, take_profit?: float|null}  $options
     */
    public function placePending(
        User $user,
        string $symbol,
        string $orderType,
        float $quantity,
        float $triggerPrice,
        array $options = [],
    ): Position {
        $symbol = strtoupper($symbol);
        $orderType = strtolower($orderType);

        if (! in_array($orderType, ['buy_limit', 'sell_limit', 'buy_stop', 'sell_stop'], true)) {
            throw new InvalidArgumentException('Invalid pending order type.');
        }

        if ($quantity < 0.01) {
            throw new InvalidArgumentException('Quantity is too small.');
        }

        if ($triggerPrice <= 0) {
            throw new InvalidArgumentException('Limit / stop price is required.');
        }

        if (! Symbols::allows($symbol)) {
            throw new InvalidArgumentException(
                'This symbol is closed by the broker. You cannot get rates on this symbol.',
            );
        }

        $account = $this->terminalAccount($user);
        $this->assertLotSize($account, $symbol, $quantity);
        $quote = $this->quoteFor($user, $symbol);

        if ($quote === null) {
            throw new InvalidArgumentException('This symbol has no rate, so the pending order was not placed.');
        }

        $this->assertPendingPrice($symbol, $orderType, $triggerPrice, $quote['bid'], $quote['ask']);
        $this->requireFreeMargin($user, $account, $symbol, $quantity, $triggerPrice);
        $side = str_starts_with($orderType, 'buy') ? 'buy' : 'sell';
        $this->assertLevels(
            $side,
            $triggerPrice,
            $triggerPrice,
            $this->optionalPrice($options['stop_loss'] ?? null),
            $this->optionalPrice($options['take_profit'] ?? null),
        );

        $position = Position::query()->create([
            'desk_ticket' => Position::nextDeskTicket(),
            'mt5_ticket' => null,
            'user_id' => $user->id,
            'client_account_id' => $account->id,
            'symbol' => $symbol,
            'side' => $side,
            'status' => Position::STATUS_PENDING,
            'order_type' => $orderType,
            'quantity' => round($quantity, 2),
            'entry_price' => null,
            'trigger_price' => $triggerPrice,
            'stop_loss' => $this->optionalPrice($options['stop_loss'] ?? null),
            'take_profit' => $this->optionalPrice($options['take_profit'] ?? null),
            'commission' => 0,
            'profit' => 0,
        ]);

        $this->mt5->queue($position, 'open');

        return $position;
    }

    public function updateLevels(User $user, Position $position, ?float $stopLoss, ?float $takeProfit): Position
    {
        if ((int) $position->user_id !== (int) $user->id) {
            throw new InvalidArgumentException('Order not found.');
        }

        if ($position->closed_at !== null || ! in_array($position->status, [Position::STATUS_OPEN, Position::STATUS_PENDING], true)) {
            throw new InvalidArgumentException('Order cannot be updated.');
        }

        $stopLoss = $this->optionalPrice($stopLoss);
        $takeProfit = $this->optionalPrice($takeProfit);

        if ($position->status === Position::STATUS_PENDING) {
            $this->assertLevels($position->side ?? 'buy', (float) $position->trigger_price, (float) $position->trigger_price, $stopLoss, $takeProfit);
        } else {
            $quote = $this->quoteFor($user, $position->symbol);

            if ($quote === null) {
                throw new InvalidArgumentException('This symbol has no rate, so the levels were not changed.');
            }

            $this->assertLevels($position->side ?? 'buy', $quote['bid'], $quote['ask'], $stopLoss, $takeProfit);
        }

        $position->forceFill([
            'stop_loss' => $this->optionalPrice($stopLoss),
            'take_profit' => $this->optionalPrice($takeProfit),
        ])->save();

        $fresh = $position->fresh();
        $this->mt5->queue($fresh, 'modify');

        return $fresh;
    }

    public function cancelPending(User $user, Position $position): Position
    {
        if ((int) $position->user_id !== (int) $user->id) {
            throw new InvalidArgumentException('Order not found.');
        }

        if ($position->status !== Position::STATUS_PENDING || $position->closed_at !== null) {
            throw new InvalidArgumentException('Only pending orders can be cancelled.');
        }

        if ($this->linkedToTerminal($position) && ! $this->awaitingTerminal($position)) {
            $this->mt5->queue($position, 'cancel');

            return $position->fresh();
        }

        return $this->voidUnsent($position);
    }

    /**
     * Refresh floating P/L, fill pending orders, and honour S/L / T/P.
     */
    public function markOpen(User $user): void
    {
        $this->fillPending($user);

        $open = Position::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [Position::STATUS_OPEN, Position::STATUS_CLOSING])
            ->whereNull('closed_at')
            ->whereNotNull('entry_price')
            ->whereNotNull('side')
            ->get();

        if ($open->isEmpty()) {
            return;
        }

        foreach ($open as $position) {
            if ($this->awaitingTerminal($position)) {
                if ((float) $position->profit !== 0.0) {
                    $position->forceFill(['profit' => 0])->save();
                }

                continue;
            }

            $quote = $this->quoteFor($user, $position->symbol);

            if ($quote === null) {
                continue;
            }

            $profit = $this->unrealized($position, $quote['bid'], $quote['ask']);
            $position->forceFill(['profit' => $profit])->save();

            if ($position->status === Position::STATUS_CLOSING) {
                continue;
            }

            if ($this->levelsHit($position, $quote['bid'], $quote['ask'])) {
                $this->requestClose($user, $position->fresh());
            }
        }
    }

    /**
     * @param  'profit'|'loss'|'all'  $filter
     * @return int closed count
     */
    public function close(User $user, string $symbol, string $filter): int
    {
        $symbol = strtoupper($symbol);

        return (int) DB::transaction(function () use ($user, $symbol, $filter): int {
            $this->markOpen($user);

            $positions = Position::query()
                ->where('user_id', $user->id)
                ->where('symbol', $symbol)
                ->where('status', Position::STATUS_OPEN)
                ->whereNull('closed_at')
                ->lockForUpdate()
                ->get();

            if ($filter === 'profit') {
                $positions = $positions->filter(fn (Position $trade): bool => (float) $trade->profit > 0);
            }

            if ($filter === 'loss') {
                $positions = $positions->filter(fn (Position $trade): bool => (float) $trade->profit < 0);
            }

            $closed = 0;

            foreach ($positions as $position) {
                $this->requestClose($user, $position);
                $closed++;
            }

            return $closed;
        });
    }

    public function closeOrder(User $user, Position $position): Position
    {
        if ((int) $position->user_id !== (int) $user->id) {
            throw new InvalidArgumentException('Order not found.');
        }

        if ($position->status !== Position::STATUS_OPEN || $position->closed_at !== null) {
            throw new InvalidArgumentException('Order is already closed.');
        }

        return DB::transaction(function () use ($user, $position): Position {
            /** @var Position $locked */
            $locked = Position::query()->whereKey($position->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== Position::STATUS_OPEN || $locked->closed_at !== null) {
                throw new InvalidArgumentException('Order is already closed.');
            }

            $this->markOpen($user);
            $locked->refresh();

            if ($locked->closed_at !== null) {
                return $locked;
            }

            return $this->requestClose($user, $locked);
        });
    }

    private function fillPending(User $user): void
    {
        $pending = Position::query()
            ->where('user_id', $user->id)
            ->where('status', Position::STATUS_PENDING)
            ->whereNull('closed_at')
            ->get();

        foreach ($pending as $order) {
            $quote = $this->quoteFor($user, $order->symbol);

            if ($quote === null || $this->linkedToTerminal($order)) {
                continue;
            }

            $trigger = (float) ($order->trigger_price ?? 0);

            if ($trigger <= 0 || ! $this->pendingShouldFill($order->order_type ?? '', $trigger, $quote['bid'], $quote['ask'])) {
                continue;
            }

            $entry = str_starts_with((string) $order->order_type, 'buy') ? $quote['ask'] : $quote['bid'];
            $cuts = $order->clientAccount === null
                ? ['customer' => 0.0, 'partner' => 0.0]
                : $this->lotCuts($order->clientAccount, (float) $order->quantity);

            $order->forceFill([
                'status' => Position::STATUS_OPEN,
                'entry_price' => $entry,
                'commission' => $cuts['customer'],
                'partner_commission' => $cuts['partner'],
                'profit' => 0,
            ])->save();
        }
    }

    private function assertPendingPrice(string $symbol, string $orderType, float $trigger, float $bid, float $ask): void
    {
        $bidText = rtrim(rtrim(number_format($bid, 5, '.', ''), '0'), '.');
        $askText = rtrim(rtrim(number_format($ask, 5, '.', ''), '0'), '.');
        $wrongSide = match ($orderType) {
            'buy_limit' => $trigger >= $ask,
            'sell_limit' => $trigger <= $bid,
            'buy_stop' => $trigger <= $ask,
            'sell_stop' => $trigger >= $bid,
            default => true,
        };

        if (! $wrongSide) {
            return;
        }

        $message = match ($orderType) {
            'buy_limit' => "Buy limit on {$symbol} must be below the ask ({$askText}). A price above the market is a Buy stop.",
            'sell_limit' => "Sell limit on {$symbol} must be above the bid ({$bidText}). A price below the market is a Sell stop.",
            'buy_stop' => "Buy stop on {$symbol} must be above the ask ({$askText}). A price below the market is a Buy limit.",
            'sell_stop' => "Sell stop on {$symbol} must be below the bid ({$bidText}). A price above the market is a Sell limit.",
            default => 'This pending price is not valid.',
        };

        throw new InvalidArgumentException($message);
    }

    private function assertLevels(string $side, float $bid, float $ask, ?float $stopLoss, ?float $takeProfit): void
    {
        $bidText = rtrim(rtrim(number_format($bid, 5, '.', ''), '0'), '.');
        $askText = rtrim(rtrim(number_format($ask, 5, '.', ''), '0'), '.');

        if ($side === 'buy') {
            if ($stopLoss !== null && $stopLoss >= $bid) {
                throw new InvalidArgumentException("Stop loss on a buy must be below the bid ({$bidText}).");
            }

            if ($takeProfit !== null && $takeProfit <= $ask) {
                throw new InvalidArgumentException("Take profit on a buy must be above the ask ({$askText}).");
            }

            return;
        }

        if ($stopLoss !== null && $stopLoss <= $ask) {
            throw new InvalidArgumentException("Stop loss on a sell must be above the ask ({$askText}).");
        }

        if ($takeProfit !== null && $takeProfit >= $bid) {
            throw new InvalidArgumentException("Take profit on a sell must be below the bid ({$bidText}).");
        }
    }

    private function pendingShouldFill(string $orderType, float $trigger, float $bid, float $ask): bool
    {
        return match ($orderType) {
            'buy_limit' => $ask <= $trigger,
            'sell_limit' => $bid >= $trigger,
            'buy_stop' => $ask >= $trigger,
            'sell_stop' => $bid <= $trigger,
            default => false,
        };
    }

    private function levelsHit(Position $position, float $bid, float $ask): bool
    {
        $stop = $position->stop_loss !== null ? (float) $position->stop_loss : null;
        $take = $position->take_profit !== null ? (float) $position->take_profit : null;

        if ($position->side === 'buy') {
            if ($stop !== null && $stop > 0 && $bid <= $stop) {
                return true;
            }

            if ($take !== null && $take > 0 && $bid >= $take) {
                return true;
            }
        }

        if ($position->side === 'sell') {
            if ($stop !== null && $stop > 0 && $ask >= $stop) {
                return true;
            }

            if ($take !== null && $take > 0 && $ask <= $take) {
                return true;
            }
        }

        return false;
    }

    /**
     * Book the close only after MT5 has the position. Until then the order stays at zero.
     */
    private function requestClose(User $user, Position $position): Position
    {
        if ($this->awaitingTerminal($position)) {
            return $this->voidUnsent($position);
        }

        if ($this->linkedToTerminal($position)) {
            if ($position->status !== Position::STATUS_CLOSING) {
                $position->forceFill(['status' => Position::STATUS_CLOSING])->save();
            }

            $this->mt5->queue($position->fresh(), 'close');

            return $position->fresh();
        }

        return $this->finalize($user, $position);
    }

    public function confirmClose(Position $position, ?float $price = null): Position
    {
        if ($position->closed_at !== null) {
            return $position;
        }

        $user = $position->user;

        if (! $user instanceof User) {
            return $position;
        }

        $quote = $this->quoteFor($user, $position->symbol);
        $useFill = $price !== null && $price > 0;
        $exit = $useFill
            ? $price
            : ($quote === null
                ? (float) ($position->entry_price ?? 0)
                : ($position->side === 'buy' ? $quote['bid'] : $quote['ask']));
        $profit = $useFill
            ? $this->unrealized(
                $position,
                $position->side === 'buy' ? $price : 0,
                $position->side === 'sell' ? $price : 0,
            )
            : ($quote === null
                ? (float) $position->profit
                : $this->unrealized($position, $quote['bid'], $quote['ask']));

        return $this->book($user, $position, $exit, $profit);
    }

    private function finalize(User $user, Position $position): Position
    {
        $quote = $this->quoteFor($user, $position->symbol);

        $exit = $quote === null
            ? (float) ($position->entry_price ?? 0)
            : ($position->side === 'buy' ? $quote['bid'] : $quote['ask']);

        $profit = $quote === null
            ? (float) $position->profit
            : $this->unrealized($position, $quote['bid'], $quote['ask']);

        $closed = $this->book($user, $position, $exit, $profit);
        $this->mt5->queue($closed, 'close');

        return $closed;
    }

    private function book(User $user, Position $position, float $exit, float $profit): Position
    {
        $net = round($profit - (float) ($position->commission ?? 0), 2);

        $position->forceFill([
            'status' => Position::STATUS_CLOSED,
            'close_price' => $exit > 0 ? $exit : null,
            'profit' => $net,
            'closed_at' => now(),
        ])->save();

        $account = $user->clientAccount;

        if ($account !== null && abs($net) >= 0.01) {
            $ticket = $position->desk_ticket ?: ('#'.$position->id);
            $this->ledger->post($account, [
                'type' => LedgerEntry::TYPE_TRADE,
                'amount' => $net,
                'desk_ticket' => $ticket,
                'mt5_ticket' => $position->mt5_ticket,
                'note' => sprintf(
                    'Close %s %s %s %.2f lots',
                    $ticket,
                    $position->side ?? 'trade',
                    $position->symbol,
                    (float) $position->quantity,
                ),
            ], $user);
        }

        return $position->fresh();
    }

    private function voidUnsent(Position $position): Position
    {
        Mt5Command::query()
            ->where('position_id', $position->id)
            ->whereIn('status', [Mt5Command::STATUS_PENDING, Mt5Command::STATUS_FAILED])
            ->update([
                'status' => Mt5Command::STATUS_FAILED,
                'attempts' => 5,
                'error' => 'Cancelled before it reached MT5.',
            ]);

        $position->forceFill([
            'status' => Position::STATUS_CANCELLED,
            'profit' => 0,
            'closed_at' => now(),
        ])->save();

        return $position->fresh();
    }

    public function usedMargin(Position $position, int $leverage): float
    {
        $quantity = (float) ($position->quantity ?? 0);
        $price = (float) ($position->entry_price ?? $position->trigger_price ?? 0);

        if ($quantity <= 0 || $price <= 0 || $leverage < 1) {
            return 0.0;
        }

        return round($this->notional($position->symbol, $quantity, $price) / $leverage, 2);
    }

    private function requireFreeMargin(User $user, ClientAccount $account, string $symbol, float $quantity, float $price): void
    {
        $leverage = max(1, (int) $account->leverage);
        $needed = round($this->notional($symbol, $quantity, $price) / $leverage, 2);
        $used = Position::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [Position::STATUS_OPEN, Position::STATUS_CLOSING, Position::STATUS_PENDING])
            ->whereNull('closed_at')
            ->get()
            ->sum(fn (Position $position): float => $this->usedMargin($position, $leverage));
        $equity = (float) $account->balance + (float) Position::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [Position::STATUS_OPEN, Position::STATUS_CLOSING])
            ->whereNull('closed_at')
            ->sum('profit');

        if ($used + $needed > $equity + 0.009) {
            throw new InvalidArgumentException('Free margin is not enough for this order.');
        }
    }

    private function notional(string $symbol, float $quantity, float $price): float
    {
        $contract = $this->contractSize($symbol);
        $key = strtoupper($symbol);
        $pricedInUsd = str_starts_with($key, 'USD')
            && ! str_starts_with($key, 'US30')
            && ! str_starts_with($key, 'US100');

        if ($pricedInUsd) {
            return $quantity * $contract;
        }

        return $quantity * $contract * $price;
    }

    private function assertLotSize(ClientAccount $account, string $symbol, float $quantity): void
    {
        $limit = Mt5Quote::query()
            ->where('mt5_account_id', $account->mt5_account_id)
            ->where('symbol', $symbol)
            ->first(['volume_min', 'volume_step']);
        $rule = MarketWatch::lotLimit($limit?->volume_min, $limit?->volume_step);
        $minimum = (float) $rule['min_lot'];
        $step = (float) $rule['lot_step'];
        $steps = $step > 0 ? round($quantity / $step) : 0;
        $offStep = abs($quantity - ($steps * $step)) > 0.0001;

        if ($quantity + 0.0000001 < $minimum || $offStep) {
            throw new InvalidArgumentException($rule['lot_note'] !== ''
                ? $rule['lot_note']
                : 'Quantity is too small.');
        }
    }

    /**
     * @return array{customer: float, partner: float}
     */
    private function lotCuts(ClientAccount $account, float $quantity): array
    {
        $account->loadMissing('mt5Account');
        $lots = round($quantity, 2);
        $pool = $account->mt5Account;

        return [
            'customer' => round(max(0, (float) ($pool?->customer_rate ?? 0)) * $lots, 2),
            'partner' => round(max(0, (float) ($pool?->partner_rate ?? 0)) * $lots, 2),
        ];
    }

    private function terminalAccount(User $user): ClientAccount
    {
        $account = $user->clientAccount;

        if ($account === null || $account->mt5_account_id === null) {
            throw new InvalidArgumentException('This login has no MT5 account, so the order was not placed.');
        }

        if ($account->archived_at !== null || ! $account->active) {
            throw new InvalidArgumentException('This account is disabled, so the order was not placed.');
        }

        return $account;
    }

    private function linkedToTerminal(Position $position): bool
    {
        $position->loadMissing('clientAccount');

        return $position->clientAccount?->mt5_account_id !== null;
    }

    private function awaitingTerminal(Position $position): bool
    {
        return $this->linkedToTerminal($position)
            && ($position->mt5_ticket === null || $position->mt5_ticket === '');
    }

    /**
     * @return float realized/unrealized P/L in account currency (simplified contract)
     */
    public function unrealized(Position $position, float $bid, float $ask): float
    {
        $entry = (float) ($position->entry_price ?? 0);
        $qty = (float) ($position->quantity ?? 0);

        if ($entry <= 0 || $qty <= 0 || ! in_array($position->side, ['buy', 'sell'], true)) {
            return round((float) $position->profit, 2);
        }

        $exit = $position->side === 'buy' ? $bid : $ask;
        $points = $position->side === 'buy' ? ($exit - $entry) : ($entry - $exit);
        $contract = $this->contractSize($position->symbol);

        return round($points * $qty * $contract, 2);
    }

    private function optionalPrice(mixed $value): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        $price = (float) $value;

        return $price > 0 ? $price : null;
    }

    private function contractSize(string $symbol): float
    {
        $key = strtoupper($symbol);

        return match (true) {
            str_starts_with($key, 'XAU') => 100.0,
            str_starts_with($key, 'XAG') => 5000.0,
            str_starts_with($key, 'XTI') => 100.0,
            str_starts_with($key, 'US30'), str_starts_with($key, 'NAS'), str_starts_with($key, 'BTC'), str_starts_with($key, 'ETH') => 1.0,
            default => 100000.0,
        };
    }
}
