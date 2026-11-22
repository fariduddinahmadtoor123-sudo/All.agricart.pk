<?php

namespace Modules\Forex\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Forex\Models\Mt5Account;
use Modules\Forex\Models\WatchlistSymbol;

class Watchlist
{
    public static function maxSymbols(): int
    {
        return Symbols::max();
    }

    /**
     * @return list<string>
     */
    public static function defaults(): array
    {
        return Symbols::allowed();
    }

    public static function isSaved(User $user): bool
    {
        return WatchlistSymbol::query()->where('user_id', $user->id)->exists();
    }

    /**
     * Saved wishlist for this user only. Empty until they save one — never auto-fills extras.
     *
     * @return list<string>
     */
    public static function for(User $user): array
    {
        $symbols = WatchlistSymbol::query()
            ->where('user_id', $user->id)
            ->orderBy('sort')
            ->orderBy('id')
            ->pluck('symbol')
            ->all();

        return Symbols::onlyAllowed($symbols);
    }

    /**
     * @return list<string>
     */
    public static function availableFor(User $user): array
    {
        return Symbols::allowed();
    }

    /**
     * Replace this user's wishlist. Order of $symbols is kept. Empty list clears it.
     *
     * @param  list<string>  $symbols
     * @return list<string>
     */
    public static function sync(User $user, array $symbols): array
    {
        $clean = collect(Symbols::onlyAllowed($symbols))->values();

        DB::transaction(function () use ($user, $clean): void {
            WatchlistSymbol::query()->where('user_id', $user->id)->delete();

            if ($clean->isEmpty()) {
                return;
            }

            $now = now();
            $rows = $clean->map(fn (string $symbol, int $index): array => [
                'user_id' => $user->id,
                'symbol' => $symbol,
                'sort' => $index,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            WatchlistSymbol::query()->insert($rows);
        });

        return $clean->all();
    }

    /**
     * @return list<string>
     */
    public static function connectorTargets(Mt5Account $account): array
    {
        $linkedUserIds = $account->clientAccounts()->pluck('user_id');

        $linked = WatchlistSymbol::query()
            ->when(
                $linkedUserIds->isNotEmpty(),
                fn ($query) => $query->whereIn('user_id', $linkedUserIds),
                fn ($query) => $query->whereRaw('1 = 0'),
            )
            ->orderBy('sort')
            ->pluck('symbol')
            ->all();

        $symbols = Symbols::onlyAllowed($linked);

        if ($symbols === []) {
            $any = WatchlistSymbol::query()->orderBy('sort')->pluck('symbol')->all();
            $symbols = Symbols::onlyAllowed($any);
        }

        return $symbols !== [] ? $symbols : self::defaults();
    }
}
