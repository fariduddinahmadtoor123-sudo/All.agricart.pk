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
    'partner_rate',
    'customer_rate',
    'deactivated_reason',
    'deactivated_at',
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
    /**
     * Why a live broker account was switched off. The row stays.
     *
     * @var array<string, string>
     */
    public const REASONS = [
        'moved' => 'Moved to another account',
        'blown' => 'Balance blown',
        'stop_out' => 'Stop out',
        'dormant' => 'Dormant',
        'broker_closed' => 'Broker closed the account',
        'compliance' => 'Compliance hold',
    ];

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

    public function reasonLabel(): string
    {
        return self::REASONS[$this->deactivated_reason] ?? '';
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
            'partner_rate' => 'float',
            'customer_rate' => 'float',
            'deactivated_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'mt5_balance' => 'float',
            'mt5_equity' => 'float',
            'mt5_margin' => 'float',
            'mt5_free_margin' => 'float',
            'mt5_profit' => 'float',
        ];
    }
}
