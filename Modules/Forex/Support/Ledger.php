<?php

namespace Modules\Forex\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Forex\Models\ClientAccount;
use Modules\Forex\Models\LedgerEntry;

class Ledger
{
    /**
     * @param  array{type: string, amount: float|int|string, note?: string|null}  $input
     */
    public function post(ClientAccount $account, array $input, ?User $by = null): LedgerEntry
    {
        $type = (string) $input['type'];
        $amount = round((float) $input['amount'], 2);
        $note = isset($input['note']) ? trim((string) $input['note']) : null;

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

        return DB::transaction(function () use ($account, $type, $signed, $note, $by): LedgerEntry {
            /** @var ClientAccount $locked */
            $locked = ClientAccount::query()->whereKey($account->id)->lockForUpdate()->firstOrFail();
            $next = round((float) $locked->balance + $signed, 2);

            if ($next < 0) {
                throw new InvalidArgumentException('Balance cannot go below zero.');
            }

            $locked->forceFill(['balance' => $next])->save();

            return LedgerEntry::query()->create([
                'client_account_id' => $locked->id,
                'type' => $type,
                'amount' => $signed,
                'balance_after' => $next,
                'note' => $note !== '' ? $note : null,
                'created_by' => $by?->id,
            ]);
        });
    }
}
