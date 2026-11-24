<?php

namespace Modules\Forex\Support;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Forex\Models\ClientAccount;
use Modules\Forex\Models\LedgerEntry;
use Modules\Forex\Models\Payment;
use Modules\Forex\Models\Wallet;
use Modules\Forex\Models\WalletEntry;

class WalletBook
{
    public function __construct(private Ledger $ledger) {}

    public function open(User $user): Wallet
    {
        return Wallet::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0],
        );
    }

    public function toWallet(ClientAccount $account, float|int|string $amount, ?User $by = null): WalletEntry
    {
        $amount = $this->amount($amount);

        return DB::transaction(function () use ($account, $amount, $by): WalletEntry {
            $this->ledger->post($account, [
                'type' => LedgerEntry::TYPE_WITHDRAWAL,
                'amount' => $amount,
                'note' => 'To wallet',
                'from' => 'Account',
                'to' => 'Wallet',
            ], $by);

            $wallet = $this->lock($account->user);
            $next = round((float) $wallet->balance + $amount, 2);
            $wallet->forceFill(['balance' => $next])->save();

            return $this->write($wallet, $account, WalletEntry::KIND_TRANSFER, $amount, $next, 'Account', 'Wallet', $by);
        });
    }

    public function toAccount(ClientAccount $account, float|int|string $amount, ?User $by = null): WalletEntry
    {
        $amount = $this->amount($amount);

        return DB::transaction(function () use ($account, $amount, $by): WalletEntry {
            $wallet = $this->lock($account->user);
            $next = round((float) $wallet->balance - $amount, 2);

            if ($next < 0) {
                throw new InvalidArgumentException('Not enough in the wallet.');
            }

            $this->ledger->post($account, [
                'type' => LedgerEntry::TYPE_DEPOSIT,
                'amount' => $amount,
                'note' => 'From wallet',
                'from' => 'Wallet',
                'to' => 'Account',
            ], $by);

            $wallet->forceFill(['balance' => $next])->save();

            return $this->write($wallet, $account, WalletEntry::KIND_TRANSFER, -$amount, $next, 'Wallet', 'Account', $by);
        });
    }

    public function deposit(ClientAccount $account, float|int|string $amount, ?User $by = null): WalletEntry
    {
        $amount = $this->amount($amount);

        return DB::transaction(function () use ($account, $amount, $by): WalletEntry {
            $wallet = $this->lock($account->user);
            $next = round((float) $wallet->balance + $amount, 2);
            $wallet->forceFill(['balance' => $next])->save();

            $entry = $this->write($wallet, $account, WalletEntry::KIND_DEPOSIT, $amount, $next, 'Desk', 'Wallet', $by);

            Payment::query()->create([
                'client_account_id' => $account->id,
                'type' => Payment::TYPE_DEPOSIT,
                'book' => Payment::BOOK_WALLET,
                'amount' => $amount,
                'status' => Payment::STATUS_PAID,
                'note' => 'Wallet deposit',
                'created_by' => $by?->id,
                'decided_by' => $by?->id,
                'paid_at' => now(),
            ]);

            return $entry;
        });
    }

    public function payOut(Payment $payment, ?User $by = null): WalletEntry
    {
        $payment->loadMissing('clientAccount.user');
        $account = $payment->clientAccount;
        $amount = $this->amount($payment->amount);

        $wallet = $this->lock($account->user);
        $next = round((float) $wallet->balance - $amount, 2);

        if ($next < 0) {
            throw new InvalidArgumentException('Not enough in the wallet.');
        }

        $wallet->forceFill(['balance' => $next])->save();

        return $this->write($wallet, $account, WalletEntry::KIND_PAYOUT, -$amount, $next, 'Wallet', 'Desk', $by);
    }

    /**
     * Staff-facing lines for money that touched the wallet and not the trading account.
     *
     * @return list<array{opened_at: ?CarbonInterface, closed_at: ?CarbonInterface, type: string, from: string, to: string, desk_ticket: ?string, mt5_ticket: ?string, amount: float, balance_after: float, note: string}>
     */
    public function externalLines(Wallet $wallet): array
    {
        return WalletEntry::query()
            ->where('wallet_id', $wallet->id)
            ->whereIn('kind', [WalletEntry::KIND_DEPOSIT, WalletEntry::KIND_PAYOUT])
            ->orderByDesc('id')
            ->get()
            ->map(fn (WalletEntry $entry): array => [
                'opened_at' => $entry->created_at,
                'closed_at' => null,
                'type' => $entry->label(),
                'from' => $entry->from_place,
                'to' => $entry->to_place,
                'desk_ticket' => null,
                'mt5_ticket' => null,
                'amount' => (float) $entry->amount,
                'balance_after' => (float) $entry->balance_after,
                'note' => 'Wallet',
            ])
            ->all();
    }

    private function lock(User $user): Wallet
    {
        $wallet = $this->open($user);

        return Wallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();
    }

    private function amount(float|int|string $amount): float
    {
        $amount = round(abs((float) $amount), 2);

        if ($amount <= 0) {
            throw new InvalidArgumentException('Amount must be greater than zero.');
        }

        return $amount;
    }

    private function write(Wallet $wallet, ClientAccount $account, string $kind, float $signed, float $balanceAfter, string $from, string $to, ?User $by): WalletEntry
    {
        return WalletEntry::query()->create([
            'wallet_id' => $wallet->id,
            'client_account_id' => $account->id,
            'kind' => $kind,
            'amount' => $signed,
            'balance_after' => $balanceAfter,
            'from_place' => $from,
            'to_place' => $to,
            'created_by' => $by?->id,
        ]);
    }
}
