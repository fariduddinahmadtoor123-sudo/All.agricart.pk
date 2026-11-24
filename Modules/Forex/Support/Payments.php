<?php

namespace Modules\Forex\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Forex\Models\ClientAccount;
use Modules\Forex\Models\Payment;

class Payments
{
    public function __construct(private Ledger $ledger, private WalletBook $wallets) {}

    /**
     * @param  array{type: string, amount: float|int|string, reference?: string|null, book?: string|null}  $input
     */
    public function record(ClientAccount $account, array $input, ?User $by = null): Payment
    {
        $type = (string) $input['type'];
        $book = (string) ($input['book'] ?? Payment::BOOK_ACCOUNT);
        $amount = round(abs((float) $input['amount']), 2);
        $reference = trim((string) ($input['reference'] ?? ''));

        if (! in_array($type, Payment::types(), true)) {
            throw new InvalidArgumentException('Unknown payment.');
        }

        if (! in_array($book, [Payment::BOOK_ACCOUNT, Payment::BOOK_WALLET], true)) {
            throw new InvalidArgumentException('Unknown book.');
        }

        if ($amount <= 0) {
            throw new InvalidArgumentException('Amount must be greater than zero.');
        }

        return Payment::query()->create([
            'client_account_id' => $account->id,
            'type' => $type,
            'book' => $book,
            'amount' => $amount,
            'status' => Payment::STATUS_PENDING,
            'reference' => $reference !== '' ? $reference : null,
            'note' => $reference !== '' ? $reference : null,
            'created_by' => $by?->id,
        ]);
    }

    public function markPaid(Payment $payment, ?User $by = null): Payment
    {
        return DB::transaction(function () use ($payment, $by): Payment {
            /** @var Payment $locked */
            $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== Payment::STATUS_PENDING) {
                throw new InvalidArgumentException('This payment is already settled.');
            }

            $locked->load('clientAccount');

            $entryId = null;

            if ($locked->book === Payment::BOOK_WALLET) {
                $this->wallets->payOut($locked, $by);
            } else {
                $entry = $this->ledger->post($locked->clientAccount, [
                    'type' => $locked->type,
                    'amount' => $locked->amount,
                    'note' => $locked->reference ?: $locked->note,
                    'from' => $locked->fromPlace(),
                    'to' => $locked->toPlace(),
                ], $by);
                $entryId = $entry->id;
            }

            $locked->forceFill([
                'status' => Payment::STATUS_PAID,
                'ledger_entry_id' => $entryId,
                'decided_by' => $by?->id,
                'paid_at' => now(),
            ])->save();

            return $locked;
        });
    }

    public function reject(Payment $payment, string $reason, ?User $by = null): Payment
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw new InvalidArgumentException('Say why this request was rejected.');
        }

        return DB::transaction(function () use ($payment, $reason, $by): Payment {
            /** @var Payment $locked */
            $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== Payment::STATUS_PENDING) {
                throw new InvalidArgumentException('This payment is already settled.');
            }

            $locked->forceFill([
                'status' => Payment::STATUS_REJECTED,
                'rejection_reason' => $reason,
                'decided_by' => $by?->id,
            ])->save();

            return $locked;
        });
    }
}
