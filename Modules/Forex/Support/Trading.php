<?php

namespace Modules\Forex\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Forex\Models\LedgerEntry;
use Modules\Forex\Models\Position;

class Trading
{
    public function __construct(private Ledger $ledger = new Ledger) {}

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

        $quote = $this->quoteFor($user, $symbol);

        if ($quote === null) {
            throw new InvalidArgumentException(
                'This symbol is closed by the broker. You cannot get rates on this symbol.',
            );
        }

        $entry = $side === 'buy' ? $quote['ask'] : $quote['bid'];
        $stopLoss = $this->optionalPrice($options['stop_loss'] ?? null);
        $takeProfit = $this->optionalPrice($options['take_profit'] ?? null);
        $commission = isset($options['commission']) && is_numeric($options['commission'])
            ? round((float) $options['commission'], 2)
            : 0.0;

        return Position::query()->create([
            'desk_ticket' => Position::nextDeskTicket(),
            'mt5_ticket' => null,
            'user_id' => $user->id,
            'client_account_id' => $user->clientAccount?->id,
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
            'profit' => 0,
        ]);
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

        $side = str_starts_with($orderType, 'buy') ? 'buy' : 'sell';

        return Position::query()->create([
            'desk_ticket' => Position::nextDeskTicket(),
            'mt5_ticket' => null,
            'user_id' => $user->id,
            'client_account_id' => $user->clientAccount?->id,
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
    }

    public function updateLevels(User $user, Position $position, ?float $stopLoss, ?float $takeProfit): Position
    {
        if ((int) $position->user_id !== (int) $user->id) {
            throw new InvalidArgumentException('Order not found.');
        }

        if ($position->closed_at !== null || ! in_array($position->status, [Position::STATUS_OPEN, Position::STATUS_PENDING], true)) {
            throw new InvalidArgumentException('Order cannot be updated.');
        }

        $position->forceFill([
            'stop_loss' => $this->optionalPrice($stopLoss),
            'take_profit' => $this->optionalPrice($takeProfit),
        ])->save();

        return $position->fresh();
    }

    public function cancelPending(User $user, Position $position): Position
    {
        if ((int) $position->user_id !== (int) $user->id) {
            throw new InvalidArgumentException('Order not found.');
        }

        if ($position->status !== Position::STATUS_PENDING || $position->closed_at !== null) {
            throw new InvalidArgumentException('Only pending orders can be cancelled.');
        }

        $position->forceFill([
            'status' => Position::STATUS_CANCELLED,
            'profit' => 0,
            'closed_at' => now(),
        ])->save();

        return $position->fresh();
    }

    /**
     * Refresh floating P/L, fill pending orders, and honour S/L / T/P.
     */
    public function markOpen(User $user): void
    {
        $this->fillPending($user);

        $open = Position::query()
            ->where('user_id', $user->id)
            ->where('status', Position::STATUS_OPEN)
            ->whereNull('closed_at')
            ->whereNotNull('entry_price')
            ->whereNotNull('side')
            ->get();

        if ($open->isEmpty()) {
            return;
        }

        foreach ($open as $position) {
            $quote = $this->quoteFor($user, $position->symbol);

            if ($quote === null) {
                continue;
            }

            $profit = $this->unrealized($position, $quote['bid'], $quote['ask']);
            $position->forceFill(['profit' => $profit])->save();

            if ($this->levelsHit($position, $quote['bid'], $quote['ask'])) {
                $this->finalize($user, $position->fresh());
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
                $this->finalize($user, $position);
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

            return $this->finalize($user, $locked);
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

            if ($quote === null) {
                continue;
            }

            $trigger = (float) ($order->trigger_price ?? 0);

            if ($trigger <= 0 || ! $this->pendingShouldFill($order->order_type ?? '', $trigger, $quote['bid'], $quote['ask'])) {
                continue;
            }

            $entry = str_starts_with((string) $order->order_type, 'buy') ? $quote['ask'] : $quote['bid'];

            $order->forceFill([
                'status' => Position::STATUS_OPEN,
                'entry_price' => $entry,
                'profit' => 0,
            ])->save();
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

    private function finalize(User $user, Position $position): Position
    {
        $quote = $this->quoteFor($user, $position->symbol);

        $exit = $quote === null
            ? (float) ($position->entry_price ?? 0)
            : ($position->side === 'buy' ? $quote['bid'] : $quote['ask']);

        $profit = $quote === null
            ? (float) $position->profit
            : $this->unrealized($position, $quote['bid'], $quote['ask']);

        $commission = (float) ($position->commission ?? 0);
        $net = round($profit - $commission, 2);

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
