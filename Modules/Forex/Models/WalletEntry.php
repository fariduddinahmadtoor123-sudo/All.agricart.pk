<?php

namespace Modules\Forex\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['wallet_id', 'client_account_id', 'kind', 'amount', 'balance_after', 'from_place', 'to_place', 'created_by'])]
class WalletEntry extends Model
{
    public const KIND_TRANSFER = 'transfer';

    public const KIND_DEPOSIT = 'deposit';

    public const KIND_PAYOUT = 'payout';

    protected $table = 'forex_wallet_entries';

    /**
     * @return BelongsTo<Wallet, $this>
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * @return BelongsTo<ClientAccount, $this>
     */
    public function clientAccount(): BelongsTo
    {
        return $this->belongsTo(ClientAccount::class, 'client_account_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function label(): string
    {
        return match ($this->kind) {
            self::KIND_DEPOSIT => 'Deposit',
            self::KIND_PAYOUT => 'Withdrawal',
            default => 'Transfer',
        };
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'balance_after' => 'float',
        ];
    }
}
