<?php

namespace Modules\Forex\Support;

use Modules\Forex\Models\ClientAccount;
use Modules\Forex\Models\Mt5Account;
use Modules\Forex\Models\Mt5Command;
use Modules\Forex\Models\Position;

class Mt5Commands
{
    public function queue(Position $position, string $action): void
    {
        if (! in_array($action, ['open', 'close', 'cancel', 'modify'], true)) {
            return;
        }

        $position->loadMissing('clientAccount');
        $mt5Id = $position->clientAccount?->mt5_account_id;

        if ($mt5Id === null || $position->desk_ticket === null || $position->desk_ticket === '') {
            return;
        }

        if ($action === 'modify') {
            $already = Mt5Command::query()
                ->where('position_id', $position->id)
                ->where('action', 'modify')
                ->where('status', Mt5Command::STATUS_PENDING)
                ->exists();
        } else {
            $already = Mt5Command::query()
                ->where('position_id', $position->id)
                ->where('action', $action)
                ->whereIn('status', [
                    Mt5Command::STATUS_PENDING,
                    Mt5Command::STATUS_DONE,
                    Mt5Command::STATUS_FAILED,
                ])
                ->exists();
        }

        if ($already) {
            return;
        }

        Mt5Command::query()->create([
            'mt5_account_id' => $mt5Id,
            'position_id' => $position->id,
            'action' => $action,
            'status' => Mt5Command::STATUS_PENDING,
            'attempts' => 0,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function pendingPayloads(Mt5Account $account): array
    {
        $this->retryFailed($account);
        $this->queueMissing($account);

        return $this->due($account);
    }

    /**
     * Pending orders already accepted by MT5, so the connector can see when one fills.
     *
     * @return list<array{position_id: int, desk_ticket: string, mt5_ticket: string}>
     */
    public function resting(Mt5Account $account): array
    {
        $clientIds = ClientAccount::query()
            ->where('mt5_account_id', $account->id)
            ->pluck('id');

        if ($clientIds->isEmpty()) {
            return [];
        }

        return Position::query()
            ->whereIn('client_account_id', $clientIds)
            ->where('status', Position::STATUS_PENDING)
            ->whereNull('closed_at')
            ->whereNotNull('mt5_ticket')
            ->where('mt5_ticket', '!=', '')
            ->orderBy('id')
            ->limit(20)
            ->get(['id', 'desk_ticket', 'mt5_ticket'])
            ->map(fn (Position $position): array => [
                'position_id' => $position->id,
                'desk_ticket' => (string) $position->desk_ticket,
                'mt5_ticket' => (string) $position->mt5_ticket,
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $result
     */
    public function confirmPendingFill(Mt5Account $account, array $result): void
    {
        $position = Position::query()->whereKey($result['position_id'] ?? null)->first();

        if ($position === null || $position->status !== Position::STATUS_PENDING || $position->closed_at !== null) {
            return;
        }

        $position->loadMissing('clientAccount');

        if ((int) $position->clientAccount?->mt5_account_id !== (int) $account->id) {
            return;
        }

        $ticket = trim((string) ($result['mt5_ticket'] ?? ''));
        $price = isset($result['price']) && is_numeric($result['price']) ? (float) $result['price'] : 0.0;

        $position->forceFill([
            'status' => Position::STATUS_OPEN,
            'mt5_ticket' => $ticket !== '' ? $ticket : $position->mt5_ticket,
            'entry_price' => $price > 0 ? $price : $position->trigger_price,
            'profit' => 0,
        ])->save();

        if ($ticket !== '' && $position->clientAccount !== null) {
            app(Ledger::class)->rememberTicket($position->clientAccount, $position->fresh());
        }
    }

    /**
     * Pending commands only. The connector asks this on a short loop so a close
     * does not wait behind a quote write.
     *
     * @return list<array<string, mixed>>
     */
    public function due(Mt5Account $account): array
    {
        return Mt5Command::query()
            ->where('mt5_account_id', $account->id)
            ->where('status', Mt5Command::STATUS_PENDING)
            ->with('position')
            ->orderBy('id')
            ->limit(20)
            ->get()
            ->filter(fn (Mt5Command $command): bool => $command->position !== null)
            ->map(fn (Mt5Command $command): array => $this->payload($command))
            ->values()
            ->all();
    }

    /**
     * @param  list<mixed>  $results
     */
    public function apply(Mt5Account $account, array $results): void
    {
        foreach ($results as $result) {
            if (! is_array($result)) {
                continue;
            }

            if (($result['filled'] ?? false) === true) {
                $this->confirmPendingFill($account, $result);

                continue;
            }

            if (! isset($result['id'])) {
                continue;
            }

            $command = Mt5Command::query()
                ->whereKey($result['id'])
                ->where('mt5_account_id', $account->id)
                ->first();

            if ($command === null) {
                continue;
            }

            if (! ($result['ok'] ?? false)) {
                $error = trim((string) ($result['error'] ?? 'Rejected by MT5.'));
                $error = $error !== '' ? mb_substr($error, 0, 255) : 'Rejected by MT5.';
                $waitingForTerminal = str_contains($error, '10027') || str_contains($error, 'disabled by client');

                $command->forceFill([
                    'status' => Mt5Command::STATUS_FAILED,
                    'error' => $error,
                    'attempts' => $waitingForTerminal ? 0 : $command->attempts + 1,
                ])->save();

                $rejected = $command->position;

                if ($rejected !== null && $rejected->status === Position::STATUS_CLOSING && $rejected->closed_at === null) {
                    $rejected->forceFill(['status' => Position::STATUS_OPEN])->save();
                }

                continue;
            }

            $ticket = isset($result['mt5_ticket']) ? trim((string) $result['mt5_ticket']) : '';
            $command->forceFill([
                'status' => Mt5Command::STATUS_DONE,
                'mt5_ticket' => $ticket !== '' ? $ticket : null,
                'error' => null,
            ])->save();

            $position = $command->position;

            if ($position === null || $ticket === '') {
                continue;
            }

            $fill = [];

            if ($position->mt5_ticket === null || $position->mt5_ticket === '') {
                $fill['mt5_ticket'] = $ticket;
            }

            if (
                $command->action === 'open'
                && $position->status === Position::STATUS_OPEN
                && isset($result['price'])
                && is_numeric($result['price'])
                && (float) $result['price'] > 0
            ) {
                $fill['entry_price'] = round((float) $result['price'], 8);
            }

            if ($fill !== []) {
                $position->forceFill($fill)->save();
                $position->refresh();
            }

            if (isset($fill['mt5_ticket'])) {
                $position->loadMissing('clientAccount');

                if ($position->clientAccount !== null) {
                    app(Ledger::class)->rememberTicket($position->clientAccount, $position);
                }
            }

            if ($command->action === 'open' && $position->closed_at !== null && $ticket !== '') {
                $this->queue($position, $position->status === Position::STATUS_CANCELLED ? 'cancel' : 'close');

                continue;
            }

            if ($command->action === 'cancel' && $position->closed_at === null) {
                $position->forceFill([
                    'status' => Position::STATUS_CANCELLED,
                    'profit' => 0,
                    'closed_at' => now(),
                ])->save();

                continue;
            }

            if ($command->action === 'close' && $position->closed_at === null) {
                $price = isset($result['price']) && is_numeric($result['price']) ? (float) $result['price'] : null;
                app(Trading::class)->confirmClose($position, $price);
            }
        }
    }

    private function retryFailed(Mt5Account $account): void
    {
        Mt5Command::query()
            ->where('mt5_account_id', $account->id)
            ->where('status', Mt5Command::STATUS_FAILED)
            ->where('attempts', '<', 5)
            ->where('updated_at', '<=', now()->subSeconds(3))
            ->update(['status' => Mt5Command::STATUS_PENDING]);
    }

    private function queueMissing(Mt5Account $account): void
    {
        $clientIds = ClientAccount::query()
            ->where('mt5_account_id', $account->id)
            ->pluck('id');

        if ($clientIds->isEmpty()) {
            return;
        }

        $working = Position::query()
            ->whereIn('client_account_id', $clientIds)
            ->whereNull('closed_at')
            ->whereIn('status', [Position::STATUS_OPEN, Position::STATUS_PENDING])
            ->where(function ($query): void {
                $query->whereNull('mt5_ticket')->orWhere('mt5_ticket', '');
            })
            ->get();

        foreach ($working as $position) {
            $this->queue($position, 'open');
        }

        $finished = Position::query()
            ->whereIn('client_account_id', $clientIds)
            ->whereNotNull('closed_at')
            ->whereNotNull('mt5_ticket')
            ->where('mt5_ticket', '!=', '')
            ->get();

        foreach ($finished as $position) {
            $action = $position->status === Position::STATUS_CANCELLED ? 'cancel' : 'close';
            $this->queue($position, $action);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Mt5Command $command): array
    {
        $position = $command->position;

        return [
            'id' => $command->id,
            'action' => $command->action,
            'desk_ticket' => $position?->desk_ticket,
            'mt5_ticket' => $position?->mt5_ticket,
            'symbol' => $position?->symbol,
            'side' => $position?->side,
            'order_type' => $position?->order_type ?: 'market',
            'volume' => $position?->quantity !== null ? (float) $position->quantity : 0,
            'price' => $position?->trigger_price !== null ? (float) $position->trigger_price : null,
            'stop_loss' => $position?->stop_loss !== null ? (float) $position->stop_loss : 0,
            'take_profit' => $position?->take_profit !== null ? (float) $position->take_profit : 0,
        ];
    }
}
