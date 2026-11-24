<?php

namespace Modules\Forex\Support;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Forex\Models\ClientAccount;
use Modules\Forex\Models\LedgerEntry;
use Modules\Forex\Models\Position;

class Ledger
{
    /**
     * @param  array{type: string, amount: float|int|string, note?: string|null, desk_ticket?: string|null, mt5_ticket?: string|null, from?: string|null, to?: string|null}  $input
     */
    public function post(ClientAccount $account, array $input, ?User $by = null): LedgerEntry
    {
        $type = (string) $input['type'];
        $amount = round((float) $input['amount'], 2);
        $note = isset($input['note']) ? trim((string) $input['note']) : null;
        $from = $this->place($input['from'] ?? null);
        $to = $this->place($input['to'] ?? null);
        $deskTicket = $this->ticket($input['desk_ticket'] ?? null);
        $mt5Ticket = $this->ticket($input['mt5_ticket'] ?? null);

        if (! in_array($type, LedgerEntry::types(), true)) {
            throw new InvalidArgumentException('Unknown ledger type.');
        }

        if ($amount == 0.0) {
            throw new InvalidArgumentException('Amount cannot be zero.');
        }

        $signed = match ($type) {
            LedgerEntry::TYPE_DEPOSIT => abs($amount),
            LedgerEntry::TYPE_WITHDRAWAL => -abs($amount),
            LedgerEntry::TYPE_ADJUSTMENT, LedgerEntry::TYPE_TRADE => $amount,
            default => $amount,
        };

        return DB::transaction(function () use ($account, $type, $signed, $note, $from, $to, $deskTicket, $mt5Ticket, $by): LedgerEntry {
            /** @var ClientAccount $locked */
            $locked = ClientAccount::query()->whereKey($account->id)->lockForUpdate()->firstOrFail();
            $next = round((float) $locked->balance + $signed, 2);

            if ($next < 0 && $type === LedgerEntry::TYPE_TRADE) {
                $signed = round(-1 * (float) $locked->balance, 2);
                $next = 0.0;
            }

            if ($next < 0) {
                throw new InvalidArgumentException('Balance cannot go below zero.');
            }

            if ($signed == 0.0) {
                return new LedgerEntry([
                    'client_account_id' => $locked->id,
                    'type' => $type,
                    'amount' => 0,
                    'balance_after' => $next,
                    'note' => $note !== '' ? $note : null,
                    'from_place' => $from,
                    'to_place' => $to,
                    'desk_ticket' => $deskTicket,
                    'mt5_ticket' => $mt5Ticket,
                    'created_by' => $by?->id,
                ]);
            }

            $locked->forceFill(['balance' => $next])->save();

            return LedgerEntry::query()->create([
                'client_account_id' => $locked->id,
                'type' => $type,
                'amount' => $signed,
                'balance_after' => $next,
                'note' => $note !== '' ? $note : null,
                'from_place' => $from,
                'to_place' => $to,
                'desk_ticket' => $deskTicket,
                'mt5_ticket' => $mt5Ticket,
                'created_by' => $by?->id,
            ]);
        });
    }

    /**
     * Opening balance is money put in. Profit is closed trade results.
     *
     * @param  list<int>  $accountIds
     * @return array<int, array{opening: string, profit: string, tone: string}>
     */
    public function figures(array $accountIds): array
    {
        $figures = [];

        foreach ($accountIds as $accountId) {
            $figures[$accountId] = ['opening' => 0.0, 'profit' => 0.0];
        }

        if ($accountIds === []) {
            return [];
        }

        $rows = LedgerEntry::query()
            ->whereIn('client_account_id', $accountIds)
            ->selectRaw('client_account_id, type, SUM(amount) as total')
            ->groupBy('client_account_id', 'type')
            ->get();

        foreach ($rows as $row) {
            $accountId = (int) $row->client_account_id;
            $total = round((float) $row->total, 2);

            if ($row->type === LedgerEntry::TYPE_TRADE) {
                $figures[$accountId]['profit'] = round($figures[$accountId]['profit'] + $total, 2);
            } else {
                $figures[$accountId]['opening'] = round($figures[$accountId]['opening'] + $total, 2);
            }
        }

        foreach ($figures as $accountId => $figure) {
            $profit = $figure['profit'];
            $figures[$accountId] = [
                'opening' => number_format($figure['opening'], 2, '.', ','),
                'profit' => ($profit > 0 ? '+' : '').number_format($profit, 2, '.', ','),
                'tone' => $profit > 0 ? 'profit' : ($profit < 0 ? 'loss' : ''),
            ];
        }

        return $figures;
    }

    /**
     * Deposits, withdrawals, and adjustments. Trades stay on the terminal history.
     *
     * @return list<array{opened_at: ?CarbonInterface, closed_at: ?CarbonInterface, type: string, desk_ticket: ?string, mt5_ticket: ?string, amount: float, balance_after: float, note: string}>
     */
    public function moneyLines(ClientAccount $account): array
    {
        return array_values(array_filter(
            $this->lines($account),
            fn (array $line): bool => $line['type'] !== 'Trade',
        ));
    }

    /**
     * One row per money movement. A trade's open and close share one row.
     *
     * @return list<array{opened_at: ?CarbonInterface, closed_at: ?CarbonInterface, type: string, from: string, to: string, desk_ticket: ?string, mt5_ticket: ?string, amount: float, balance_after: float, note: string}>
     */
    public function lines(ClientAccount $account): array
    {
        /** @var Collection<int, LedgerEntry> $entries */
        $entries = LedgerEntry::query()
            ->where('client_account_id', $account->id)
            ->orderBy('id')
            ->get();

        $rows = [];
        /** @var array<string, list<LedgerEntry>> $trades */
        $trades = [];

        foreach ($entries as $entry) {
            $deskTicket = $this->ticket($entry->desk_ticket);

            if ($entry->type === LedgerEntry::TYPE_TRADE && $deskTicket !== null) {
                $trades[$deskTicket][] = $entry;

                continue;
            }

            $places = $this->places($entry);

            $rows[] = [
                'opened_at' => $entry->created_at,
                'closed_at' => null,
                'type' => $entry->label(),
                'from' => $places[0],
                'to' => $places[1],
                'desk_ticket' => $this->ticket($entry->desk_ticket),
                'mt5_ticket' => $this->ticket($entry->mt5_ticket),
                'amount' => (float) $entry->amount,
                'balance_after' => (float) $entry->balance_after,
                'note' => trim((string) $entry->note),
            ];
        }

        foreach ($trades as $group) {
            $rows[] = $this->tradeLine($group);
        }

        usort($rows, function (array $left, array $right): int {
            $leftAt = $left['closed_at'] ?? $left['opened_at'];
            $rightAt = $right['closed_at'] ?? $right['opened_at'];

            return $rightAt <=> $leftAt;
        });

        return $rows;
    }

    /**
     * @param  list<LedgerEntry>  $entries
     * @return array{opened_at: ?CarbonInterface, closed_at: ?CarbonInterface, type: string, from: string, to: string, desk_ticket: ?string, mt5_ticket: ?string, amount: float, balance_after: float, note: string}
     */
    private function tradeLine(array $entries): array
    {
        $open = null;
        $close = null;

        foreach ($entries as $entry) {
            if ($this->isClose($entry)) {
                $close = $entry;
            } else {
                $open = $entry;
            }
        }

        $shown = $close ?? $open ?? $entries[0];
        $note = trim((string) $shown->note);
        $note = preg_replace('/^(Open|Close)\s+\S+\s+/', '', $note) ?? $note;

        return [
            'opened_at' => $open?->created_at,
            'closed_at' => $close?->created_at,
            'type' => 'Trade',
            'from' => '',
            'to' => '',
            'desk_ticket' => $this->ticket($shown->desk_ticket),
            'mt5_ticket' => $this->ticket($shown->mt5_ticket) ?? $this->ticket(($open ?? $close)?->mt5_ticket),
            'amount' => (float) $shown->amount,
            'balance_after' => (float) $shown->balance_after,
            'note' => $note,
        ];
    }

    private function isClose(LedgerEntry $entry): bool
    {
        $note = trim((string) $entry->note);

        if (str_starts_with($note, 'Close ')) {
            return true;
        }

        return ! str_starts_with($note, 'Open ') && (float) $entry->amount !== 0.0;
    }

    public function rememberTicket(ClientAccount $account, Position $position): void
    {
        $deskTicket = $this->ticket($position->desk_ticket);
        $mt5Ticket = $this->ticket($position->mt5_ticket);

        if ($deskTicket === null || $mt5Ticket === null) {
            return;
        }

        $exists = LedgerEntry::query()
            ->where('client_account_id', $account->id)
            ->where('desk_ticket', $deskTicket)
            ->where('mt5_ticket', $mt5Ticket)
            ->exists();

        if ($exists) {
            return;
        }

        LedgerEntry::query()->create([
            'client_account_id' => $account->id,
            'type' => LedgerEntry::TYPE_TRADE,
            'amount' => 0,
            'balance_after' => $account->balance,
            'note' => sprintf(
                'Open %s %s %s %.2f lots',
                $deskTicket,
                $position->side ?? 'trade',
                $position->symbol,
                (float) $position->quantity,
            ),
            'desk_ticket' => $deskTicket,
            'mt5_ticket' => $mt5Ticket,
            'created_by' => $position->user_id,
        ]);
    }

    private function place(mixed $value): ?string
    {
        $place = trim((string) $value);

        return $place !== '' ? $place : null;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function places(LedgerEntry $entry): array
    {
        $from = trim((string) $entry->from_place);
        $to = trim((string) $entry->to_place);

        if ($from !== '' || $to !== '') {
            return [$from, $to];
        }

        return match ($entry->type) {
            LedgerEntry::TYPE_DEPOSIT => ['Desk', 'Account'],
            LedgerEntry::TYPE_WITHDRAWAL => ['Account', 'Desk'],
            default => ['', ''],
        };
    }

    private function ticket(mixed $value): ?string
    {
        $ticket = trim((string) $value);

        return $ticket !== '' ? $ticket : null;
    }
}
