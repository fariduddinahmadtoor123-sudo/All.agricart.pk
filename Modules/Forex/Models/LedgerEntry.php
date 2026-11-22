<?php

namespace Modules\Forex\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['client_account_id', 'type', 'amount', 'balance_after', 'note', 'created_by'])]
class LedgerEntry extends Model
{
    public const TYPE_DEPOSIT = 'deposit';

    public const TYPE_WITHDRAWAL = 'withdrawal';

    public const TYPE_ADJUSTMENT = 'adjustment';

    public const TYPE_TRADE = 'trade';

    protected $table = 'forex_ledger_entries';

    /**
     * @return list<string>
     */
    public static function types(): array
    {
        return [
            self::TYPE_DEPOSIT,
            self::TYPE_WITHDRAWAL,
            self::TYPE_ADJUSTMENT,
            self::TYPE_TRADE,
        ];
    }

    public function label(): string
    {
        return match ($this->type) {
            self::TYPE_DEPOSIT => 'Deposit',
            self::TYPE_WITHDRAWAL => 'Withdrawal',
            self::TYPE_ADJUSTMENT => 'Adjustment',
            self::TYPE_TRADE => 'Trade',
            default => ucfirst($this->type),
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
            'balance_after' => 'float',
        ];
    }
}
