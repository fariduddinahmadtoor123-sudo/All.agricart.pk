<?php

namespace Modules\Forex\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'name',
    'broker',
    'company',
    'server',
    'login',
    'active',
    'connector_token',
    'last_seen_at',
    'mt5_balance',
    'mt5_equity',
    'mt5_margin',
    'mt5_free_margin',
    'mt5_profit',
])]
class Mt5Account extends Model
{
    protected $table = 'forex_mt5_accounts';

    protected static function booted(): void
    {
        static::creating(function (Mt5Account $account): void {
            if (blank($account->connector_token)) {
                $account->connector_token = Str::random(48);
            }
        });
    }

    public function ensureConnectorToken(): string
    {
        if (blank($this->connector_token)) {
            $this->forceFill(['connector_token' => Str::random(48)])->save();
        }

        return (string) $this->connector_token;
    }

    public function connectorIsOnline(): bool
    {
        return $this->last_seen_at !== null && $this->last_seen_at->greaterThan(now()->subSeconds(20));
    }

    /**
     * @return HasMany<ClientAccount, $this>
     */
    public function clientAccounts(): HasMany
    {
        return $this->hasMany(ClientAccount::class, 'mt5_account_id');
    }

    /**
     * @return HasMany<Mt5Quote, $this>
     */
    public function quotes(): HasMany
    {
        return $this->hasMany(Mt5Quote::class, 'mt5_account_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'last_seen_at' => 'datetime',
            'mt5_balance' => 'float',
            'mt5_equity' => 'float',
            'mt5_margin' => 'float',
            'mt5_free_margin' => 'float',
            'mt5_profit' => 'float',
        ];
    }
}
