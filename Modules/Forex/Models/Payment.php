<?php

namespace Modules\Forex\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['client_account_id', 'type', 'book', 'amount', 'status', 'note', 'reference', 'rejection_reason', 'ledger_entry_id', 'created_by', 'decided_by', 'paid_at'])]
class Payment extends Model
{
    public const TYPE_DEPOSIT = 'deposit';

    public const TYPE_WITHDRAWAL = 'withdrawal';

    public const BOOK_ACCOUNT = 'account';

    public const BOOK_WALLET = 'wallet';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_REJECTED = 'rejected';

    protected $table = 'forex_payments';

    /**
     * @return list<string>
     */
    public static function types(): array
    {
        return [
            self::TYPE_DEPOSIT,
            self::TYPE_WITHDRAWAL,
        ];
    }

    /**
     * @return list<string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_PAID,
            self::STATUS_REJECTED,
        ];
    }

    public function typeLabel(): string
    {
        return $this->type === self::TYPE_WITHDRAWAL ? 'Withdrawal' : 'Deposit';
    }

    public function fromPlace(): string
    {
        if ($this->book === self::BOOK_WALLET) {
            return $this->type === self::TYPE_WITHDRAWAL ? 'Wallet' : 'Desk';
        }

        return $this->type === self::TYPE_WITHDRAWAL ? 'Account' : 'Desk';
    }

    public function toPlace(): string
    {
        if ($this->book === self::BOOK_WALLET) {
            return $this->type === self::TYPE_WITHDRAWAL ? 'Desk' : 'Wallet';
        }

        return $this->type === self::TYPE_WITHDRAWAL ? 'Desk' : 'Account';
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PAID => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            default => 'Pending',
        };
    }

    /**
     * @return BelongsTo<ClientAccount, $this>
     */
    public function clientAccount(): BelongsTo
    {
        return $this->belongsTo(ClientAccount::class, 'client_account_id');
    }

    /**
     * @return BelongsTo<LedgerEntry, $this>
     */
    public function ledgerEntry(): BelongsTo
    {
        return $this->belongsTo(LedgerEntry::class, 'ledger_entry_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'paid_at' => 'datetime',
        ];
    }
}
