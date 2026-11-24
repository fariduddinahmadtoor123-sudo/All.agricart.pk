<?php

namespace Modules\Forex\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

#[Fillable([
    'desk_ticket',
    'mt5_ticket',
    'user_id',
    'client_account_id',
    'symbol',
    'side',
    'status',
    'order_type',
    'quantity',
    'entry_price',
    'trigger_price',
    'close_price',
    'stop_loss',
    'take_profit',
    'commission',
    'partner_commission',
    'profit',
    'closed_at',
])]
class Position extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSING = 'closing';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'forex_positions';

    public static function nextDeskTicket(): string
    {
        return DB::transaction(function (): string {
            $latest = self::query()
                ->whereNotNull('desk_ticket')
                ->lockForUpdate()
                ->orderByDesc('id')
                ->value('desk_ticket');

            $sequence = 100001;

            if (is_string($latest) && preg_match('/^D-(\d+)$/', $latest, $matches) === 1) {
                $sequence = ((int) $matches[1]) + 1;
            }

            return 'D-'.$sequence;
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
     * @return BelongsTo<ClientAccount, $this>
     */
    public function clientAccount(): BelongsTo
    {
        return $this->belongsTo(ClientAccount::class, 'client_account_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'entry_price' => 'decimal:8',
            'trigger_price' => 'decimal:8',
            'close_price' => 'decimal:8',
            'stop_loss' => 'decimal:8',
            'take_profit' => 'decimal:8',
            'commission' => 'decimal:2',
            'partner_commission' => 'decimal:2',
            'profit' => 'decimal:2',
            'closed_at' => 'datetime',
        ];
    }
}
