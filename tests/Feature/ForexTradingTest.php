<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Forex\Models\ClientAccount;
use Modules\Forex\Models\Mt5Account;
use Modules\Forex\Models\Mt5Quote;
use Modules\Forex\Models\Position;
use Modules\Forex\Support\MarketWatch;
use Tests\TestCase;

class ForexTradingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_sent_to_login_from_forex_trading(): void
    {
        $this->get('/forex')->assertRedirect('/login');
        $this->get('/forex/market-watch')->assertRedirect('/login');
    }

    public function test_a_user_opens_forex_trading(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/forex')
            ->assertRedirect(route('forex.market-watch'));

        $this->actingAs($user)
            ->get(route('forex.market-watch'))
            ->assertOk()
            ->assertSee('Trade Terminal')
            ->assertSee('Forex Trading')
            ->assertDontSee('cTrader')
            ->assertDontSee('Trading platform')
            ->assertSee('EURUSD')
            ->assertSee('XAUUSD')
            ->assertSeeInOrder(['Time', 'Quantity', 'Lots', 'P/L'])
            ->assertSee('Sell = 0.00')
            ->assertSee('Buy = 0.00')
            ->assertSeeInOrder(['Balance', 'Equity', 'P/L', 'Margin', 'Free margin'])
            ->assertSee('10,000.00')
            ->assertSee('0.00')
            ->assertDontSee('text-sm font-semibold tracking-wide">Market Watch', false)
            ->assertSee('Market sessions')
            ->assertSeeInOrder(['Sydney', 'Tokyo', 'London', 'New York'])
            ->assertSee('Liquidity')
            ->assertSee('Fast trading')
            ->assertDontSee('Check all')
            ->assertDontSee('Uncheck all')
            ->assertSee('Arm EURUSD')
            ->assertSee('Quantity for EURUSD')
            ->assertSee('Buy EURUSD')
            ->assertSee('Sell EURUSD')
            ->assertSee('My Wishlist')
            ->assertSee('Forex')
            ->assertSee('Commodities')
            ->assertSee('Crypto')
            ->assertSee('Indexes')
            ->assertSee('Bid')
            ->assertSee('Ask')
            ->assertSee('Spread')
            ->assertSeeInOrder(['Low', 'Range', 'High'])
            ->assertSee('Close lots for this symbol')
            ->assertSeeInOrder(['Profit', 'Loss', 'All'])
            ->assertSee('—')
            ->assertSee('Range')
            ->assertSee('Time')
            ->assertSee('P/L')
            ->assertSee('Close profitable EURUSD trades')
            ->assertSee('Close losing EURUSD trades')
            ->assertSee('Close all EURUSD trades')
            ->assertSee('Quotes above')
            ->assertSee('Orders')
            ->assertSeeInOrder(['Open', 'Pending', 'History'])
            ->assertSee('No pending orders.')
            ->assertSee('New pending order')
            ->assertSee('Lot calculator')
            ->assertDontSee('Stop loss for EURUSD')
            ->assertDontSee('Take profit for EURUSD')
            ->assertSee('Maximize Symbols')
            ->assertSee('Minimize Orders')
            ->assertSee('Maximize Tools')
            ->assertSee('No closed orders.')
            ->assertDontSee('The online shop.');
    }

    public function test_one_click_closes_every_profitable_trade_for_a_symbol(): void
    {
        $user = User::factory()->create();

        foreach ([10.00, 4.50, 1.25, 8.00, 2.00, 6.40, 3.10, 9.00, 5.50, 7.25, -2.00, -6.50] as $profit) {
            Position::query()->create([
                'user_id' => $user->id,
                'symbol' => 'EURUSD',
                'profit' => $profit,
            ]);
        }

        Position::query()->create([
            'user_id' => $user->id,
            'symbol' => 'XAUUSD',
            'profit' => 20,
        ]);

        $this->actingAs($user)
            ->post(route('forex.positions.close'), ['symbol' => 'EURUSD', 'side' => 'profit'])
            ->assertRedirect(route('forex.market-watch'));

        $this->assertSame(2, Position::query()->where('user_id', $user->id)->where('symbol', 'EURUSD')->whereNull('closed_at')->count());
        $this->assertSame(10, Position::query()->where('user_id', $user->id)->where('symbol', 'EURUSD')->whereNotNull('closed_at')->count());
        $this->assertNull(Position::query()->where('symbol', 'XAUUSD')->value('closed_at'));
    }

    public function test_one_click_closes_every_losing_trade_for_a_symbol(): void
    {
        $user = User::factory()->create();

        foreach ([12.00, -1.00, -2.00, -3.00, -4.00, -5.00, -6.00, -7.00, -8.00, -9.00, -10.00] as $profit) {
            Position::query()->create([
                'user_id' => $user->id,
                'symbol' => 'XAUUSD',
                'profit' => $profit,
            ]);
        }

        $this->actingAs($user)
            ->post(route('forex.positions.close'), ['symbol' => 'XAUUSD', 'side' => 'loss'])
            ->assertRedirect(route('forex.market-watch'));

        $open = Position::query()->where('user_id', $user->id)->where('symbol', 'XAUUSD')->whereNull('closed_at')->get();

        $this->assertCount(1, $open);
        $this->assertSame('12.00', $open->first()->profit);
    }

    public function test_one_click_closes_every_trade_for_a_symbol(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        Position::query()->create(['user_id' => $user->id, 'symbol' => 'GBPUSD', 'profit' => 5]);
        Position::query()->create(['user_id' => $user->id, 'symbol' => 'GBPUSD', 'profit' => -5]);
        Position::query()->create(['user_id' => $other->id, 'symbol' => 'GBPUSD', 'profit' => 9]);

        $this->actingAs($user)
            ->post(route('forex.positions.close'), ['symbol' => 'GBPUSD', 'side' => 'all'])
            ->assertRedirect(route('forex.market-watch'));

        $this->assertSame(0, Position::query()->where('user_id', $user->id)->whereNull('closed_at')->count());
        $this->assertNull(Position::query()->where('user_id', $other->id)->value('closed_at'));
    }

    public function test_a_guest_cannot_close_trades(): void
    {
        $this->post(route('forex.positions.close'), ['symbol' => 'EURUSD', 'side' => 'all'])
            ->assertRedirect('/login');
    }

    public function test_buy_opens_one_buy_at_the_typed_quantity(): void
    {
        $user = User::factory()->create();
        $this->seedLiveQuote('XAUUSD', 2654.30, 2654.62, 2);

        $this->actingAs($user)
            ->post(route('forex.positions.open'), [
                'armed' => '1',
                'symbol' => 'XAUUSD',
                'side' => 'buy',
                'quantity' => '0.20',
            ])
            ->assertRedirect(route('forex.market-watch'));

        $opened = Position::query()->where('user_id', $user->id)->where('symbol', 'XAUUSD')->get();

        $this->assertCount(1, $opened);
        $this->assertSame('buy', $opened->first()->side);
        $this->assertSame('0.20', $opened->first()->quantity);
        $this->assertSame(2654.62, (float) $opened->first()->entry_price);
        $this->assertSame(0.0, (float) $opened->first()->profit);
        $this->assertSame('D-100001', $opened->first()->desk_ticket);
        $this->assertNull($opened->first()->mt5_ticket);
    }

    public function test_sell_opens_one_sell_at_the_typed_quantity(): void
    {
        $user = User::factory()->create();
        $this->seedLiveQuote('EURUSD', 1.08542, 1.08554, 5);

        $this->actingAs($user)
            ->post(route('forex.positions.open'), [
                'armed' => '1',
                'symbol' => 'EURUSD',
                'side' => 'sell',
                'quantity' => '0.10',
            ])
            ->assertRedirect(route('forex.market-watch'));

        $opened = Position::query()->where('user_id', $user->id)->first();

        $this->assertSame('sell', $opened->side);
        $this->assertSame('0.10', $opened->quantity);
        $this->assertSame(1.08542, (float) $opened->entry_price);
    }

    public function test_buy_stays_off_until_the_row_is_armed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('forex.positions.open'), [
                'symbol' => 'EURUSD',
                'side' => 'buy',
                'quantity' => '0.10',
            ])
            ->assertSessionHasErrors('armed');

        $this->assertSame(0, Position::query()->count());
    }

    public function test_orders_section_shows_open_trades_and_history(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        Position::query()->create([
            'desk_ticket' => 'D-100010',
            'user_id' => $user->id,
            'symbol' => 'GBPUSD',
            'side' => 'sell',
            'quantity' => 0.10,
            'entry_price' => 1.27,
            'stop_loss' => 1.28,
            'take_profit' => 1.25,
            'commission' => 0.50,
            'profit' => -2,
        ]);
        Position::query()->create([
            'desk_ticket' => 'D-100011',
            'user_id' => $user->id,
            'symbol' => 'EURUSD',
            'side' => 'buy',
            'quantity' => 0.20,
            'entry_price' => 1.08,
            'profit' => 1,
            'closed_at' => now(),
        ]);
        Position::query()->create([
            'desk_ticket' => 'D-100099',
            'user_id' => $other->id,
            'symbol' => 'BTCUSD',
            'side' => 'buy',
            'quantity' => 1,
            'profit' => 50,
        ]);

        $this->actingAs($user)
            ->get(route('forex.market-watch'))
            ->assertOk()
            ->assertSee('D-100010')
            ->assertSee('D-100011')
            ->assertDontSee('D-100099')
            ->assertSee('0.10')
            ->assertSee('0.20')
            ->assertSee('S/L')
            ->assertSee('T/P')
            ->assertSee('Commission')
            ->assertSee('0.50')
            ->assertSee('Sell = 0.10')
            ->assertSee('Buy = 0.00')
            ->assertSee('Close order D-100010');
    }

    public function test_pending_orders_appear_in_the_orders_panel(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('forex.orders.pending'), [
                'symbol' => 'EURUSD',
                'order_type' => 'buy_limit',
                'quantity' => '0.05',
                'trigger_price' => '1.08000',
                'stop_loss' => '1.07000',
                'take_profit' => '1.10000',
            ])
            ->assertRedirect(route('forex.market-watch'));

        $pending = Position::query()->where('user_id', $user->id)->first();
        $this->assertSame(Position::STATUS_PENDING, $pending->status);
        $this->assertSame('buy_limit', $pending->order_type);

        $this->actingAs($user)
            ->get(route('forex.market-watch'))
            ->assertOk()
            ->assertSee($pending->desk_ticket)
            ->assertSee('Buy Limit')
            ->assertSee('Cancel pending '.$pending->desk_ticket);
    }

    public function test_trade_terminal_uses_desk_equity_not_mt5_snapshot(): void
    {
        $user = User::factory()->create();
        ClientAccount::query()->create([
            'number' => 'TA-100777',
            'user_id' => $user->id,
            'balance' => 3333.33,
            'active' => true,
        ]);

        $feed = Mt5Account::query()->create([
            'name' => 'Linked',
            'broker' => 'Demo',
            'server' => 'Demo',
            'login' => '999001',
            'active' => true,
            'last_seen_at' => now(),
            'mt5_balance' => 12500.55,
            'mt5_equity' => 12610.20,
        ]);

        ClientAccount::query()->where('user_id', $user->id)->update(['mt5_account_id' => $feed->id]);

        $meters = collect(MarketWatch::account($user->fresh()))->pluck('value', 'label');

        $this->assertSame('3,333.33', $meters['Balance']);
        $this->assertSame('3,333.33', $meters['Equity']);
        $this->assertNotSame('12,610.20', $meters['Equity']);
    }

    public function test_a_user_can_close_their_own_order_from_the_orders_panel(): void
    {
        $user = User::factory()->create();
        $this->seedLiveQuote('EURUSD', 1.08542, 1.08554, 5);

        $position = Position::query()->create([
            'desk_ticket' => 'D-100020',
            'user_id' => $user->id,
            'symbol' => 'EURUSD',
            'side' => 'buy',
            'quantity' => 0.10,
            'entry_price' => 1.08000,
            'profit' => 0,
        ]);

        $this->actingAs($user)
            ->post(route('forex.orders.close', $position))
            ->assertRedirect(route('forex.market-watch'));

        $this->assertNotNull($position->fresh()->closed_at);
    }

    public function test_a_user_cannot_close_another_users_order(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $position = Position::query()->create([
            'desk_ticket' => 'D-100021',
            'user_id' => $owner->id,
            'symbol' => 'EURUSD',
            'side' => 'buy',
            'quantity' => 0.10,
            'entry_price' => 1.08,
            'profit' => 0,
        ]);

        $this->actingAs($intruder)
            ->post(route('forex.orders.close', $position))
            ->assertForbidden();

        $this->assertNull($position->fresh()->closed_at);
    }

    public function test_a_guest_cannot_open_a_buy(): void
    {
        $this->post(route('forex.positions.open'), [
            'armed' => '1',
            'symbol' => 'EURUSD',
            'side' => 'buy',
            'quantity' => '0.01',
        ])->assertRedirect('/login');
    }

    public function test_closing_a_trade_posts_realized_pnl_to_the_ledger(): void
    {
        $user = User::factory()->create();
        $account = ClientAccount::query()->create([
            'number' => 'TA-100001',
            'user_id' => $user->id,
            'balance' => 1000,
            'active' => true,
        ]);
        $this->seedLiveQuote('BTCUSD', 96420, 96480, 2);

        Position::query()->create([
            'user_id' => $user->id,
            'symbol' => 'BTCUSD',
            'side' => 'buy',
            'quantity' => 1,
            'entry_price' => 90000,
            'profit' => 0,
        ]);

        $this->actingAs($user)
            ->post(route('forex.positions.close'), ['symbol' => 'BTCUSD', 'side' => 'all'])
            ->assertRedirect(route('forex.market-watch'));

        $closed = Position::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($closed->closed_at);
        $this->assertSame(6420.0, (float) $closed->profit);
        $this->assertSame(7420.0, (float) $account->fresh()->balance);
    }

    private function seedLiveQuote(string $symbol, float $bid, float $ask, int $digits): void
    {
        $feed = Mt5Account::query()->create([
            'name' => 'Live',
            'broker' => 'Demo',
            'server' => 'Demo',
            'login' => (string) random_int(1000, 9999),
            'active' => true,
            'last_seen_at' => now(),
        ]);

        Mt5Quote::query()->create([
            'mt5_account_id' => $feed->id,
            'symbol' => $symbol,
            'bid' => $bid,
            'ask' => $ask,
            'high' => $ask,
            'low' => $bid,
            'spread' => $ask - $bid,
            'digits' => $digits,
            'tick_at' => now(),
        ]);
    }

    public function test_market_sessions_follow_utc_open_order(): void
    {
        $labels = fn (Carbon $at): array => collect(MarketWatch::sessions($at))
            ->filter(fn (array $session): bool => $session['active'])
            ->pluck('label')
            ->all();

        $this->assertSame(['Sydney'], $labels(Carbon::parse('2026-11-22 23:00:00', 'UTC')));
        $this->assertSame(['Sydney', 'Tokyo'], $labels(Carbon::parse('2026-11-22 01:00:00', 'UTC')));
        $this->assertSame(['Tokyo', 'London'], $labels(Carbon::parse('2026-11-22 08:30:00', 'UTC')));
        $this->assertSame(['London', 'New York'], $labels(Carbon::parse('2026-11-22 14:00:00', 'UTC')));
        $this->assertSame(['New York'], $labels(Carbon::parse('2026-11-22 18:00:00', 'UTC')));
        $this->assertSame(['Sydney'], $labels(Carbon::parse('2026-11-22 22:30:00', 'UTC')));
    }

    public function test_liquidity_gauge_follows_session_overlap(): void
    {
        $this->assertSame('low', MarketWatch::liquidity(Carbon::parse('2026-11-22 23:00:00', 'UTC'))['level']);
        $this->assertSame('medium', MarketWatch::liquidity(Carbon::parse('2026-11-22 01:00:00', 'UTC'))['level']);
        $this->assertSame('high', MarketWatch::liquidity(Carbon::parse('2026-11-22 14:00:00', 'UTC'))['level']);
        $this->assertSame(100, MarketWatch::liquidity(Carbon::parse('2026-11-22 14:00:00', 'UTC'))['percent']);
    }
}
