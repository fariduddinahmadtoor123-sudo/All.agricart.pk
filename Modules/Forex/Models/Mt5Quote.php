<?php

namespace Modules\Forex\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['mt5_account_id', 'symbol', 'bid', 'ask', 'high', 'low', 'spread', 'digits', 'volume_min', 'volume_step', 'tick_at'])]
class Mt5Quote extends Model
{
    protected $table = 'forex_mt5_quotes';

    /**
     * @return BelongsTo<Mt5Account, $this>
     */
    public function mt5Account(): BelongsTo
    {
        return $this->belongsTo(Mt5Account::class, 'mt5_account_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'bid' => 'float',
            'ask' => 'float',
            'high' => 'float',
            'low' => 'float',
            'spread' => 'float',
            'digits' => 'integer',
            'volume_min' => 'float',
            'volume_step' => 'float',
            'tick_at' => 'datetime',
        ];
    }
}
