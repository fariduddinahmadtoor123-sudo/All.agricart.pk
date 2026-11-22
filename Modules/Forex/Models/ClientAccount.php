<?php

namespace Modules\Forex\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

#[Fillable(['number', 'user_id', 'mt5_account_id', 'balance', 'active'])]
class ClientAccount extends Model
{
    protected $table = 'forex_client_accounts';

    public static function nextNumber(): string
    {
        return DB::transaction(function (): string {
            $latest = self::query()->lockForUpdate()->orderByDesc('id')->value('number');
            $sequence = $latest === null ? 100001 : ((int) str_replace('TA-', '', $latest) + 1);

            return 'TA-'.$sequence;
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Mt5Account, $this>
     */
    public function mt5Account(): BelongsTo
    {
        return $this->belongsTo(Mt5Account::class, 'mt5_account_id');
    }

    /**
     * @return HasMany<LedgerEntry, $this>
     */
    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class, 'client_account_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'balance' => 'float',
            'active' => 'boolean',
        ];
    }
}
