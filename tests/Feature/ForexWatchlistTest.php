<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Forex\Models\Mt5Account;
use Modules\Forex\Models\Mt5Quote;
use Modules\Forex\Models\WatchlistSymbol;
use Modules\Forex\Support\Watchlist;
use Tests\TestCase;

class ForexWatchlistTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_save_a_custom_symbol_watchlist(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('forex.market-watch.watchlist'), [
                '_form' => 'watchlist',
                'symbols' => ['BTCUSD', 'XAUUSD'],
                'custom' => 'EURUSD',
            ])
            ->assertRedirect(route('forex.market-watch'));

        $this->assertSame(
            ['BTCUSD', 'XAUUSD', 'EURUSD'],
            WatchlistSymbol::query()->where('user_id', $user->id)->orderBy('sort')->pluck('symbol')->all(),
        );

        $this->actingAs($user)
            ->get(route('forex.market-watch'))
            ->assertOk()
            ->assertSee('My Wishlist')
            ->assertSee('Commodities')
            ->assertSee('BTCUSD')
            ->assertSee('XAUUSD')
            ->assertSee('EURUSD')
            ->assertSee('Fast trading: arm a row, then one click buys or sells.', false);
    }

    public function test_watchlist_order_and_contents_persist_for_that_user_only(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($user)
            ->post(route('forex.market-watch.watchlist'), [
                '_form' => 'watchlist',
                'symbols' => ['XAUUSD', 'EURUSD', 'BTCUSD'],
            ])
            ->assertRedirect(route('forex.market-watch'));

        $this->actingAs($other)
            ->post(route('forex.market-watch.watchlist'), [
                '_form' => 'watchlist',
                'symbols' => ['US30'],
            ])
            ->assertRedirect(route('forex.market-watch'));

        $this->assertSame(['XAUUSD', 'EURUSD', 'BTCUSD'], Watchlist::for($user->fresh()));
        $this->assertSame(['US30'], Watchlist::for($other->fresh()));

        $this->actingAs($user)
            ->get(route('forex.market-watch'))
            ->assertOk()
            ->assertSeeInOrder(['XAUUSD', 'EURUSD', 'BTCUSD'])
            ->assertDontSee('data-symbol="US30"', false);
    }

    public function test_a_user_can_clear_symbols_from_the_wishlist_without_auto_refill(): void
    {
        $user = User::factory()->create();

        Watchlist::sync($user, ['EURUSD', 'GBPUSD']);

        $this->actingAs($user)
            ->post(route('forex.market-watch.watchlist'), [
                '_form' => 'watchlist',
                'symbols' => [],
            ])
            ->assertRedirect(route('forex.market-watch'));

        $this->assertSame([], Watchlist::for($user->fresh()));
        $this->assertSame(0, WatchlistSymbol::query()->where('user_id', $user->id)->count());
    }

    public function test_unknown_custom_symbol_is_rejected_as_broker_closed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('forex.market-watch'))
            ->post(route('forex.market-watch.watchlist'), [
                '_form' => 'watchlist',
                'symbols' => ['EURUSD'],
                'custom' => 'FAKECOIN',
            ])
            ->assertRedirect(route('forex.market-watch'))
            ->assertSessionHasErrors('custom');

        $this->assertSame(
            [],
            WatchlistSymbol::query()->where('user_id', $user->id)->pluck('symbol')->all(),
        );
    }

    public function test_live_mt5_symbols_appear_as_available_choices(): void
    {
        $user = User::factory()->create();
        $account = Mt5Account::query()->create([
            'name' => 'Pool',
            'broker' => 'Demo',
            'server' => 'Demo',
            'login' => '1',
            'active' => true,
            'last_seen_at' => now(),
        ]);
        Mt5Quote::query()->create([
            'mt5_account_id' => $account->id,
            'symbol' => 'BTCUSD',
            'bid' => 60000,
            'ask' => 60010,
            'tick_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('forex.market-watch'))
            ->assertOk()
            ->assertSee('data-wl-add="BTCUSD"', false);
    }
}
