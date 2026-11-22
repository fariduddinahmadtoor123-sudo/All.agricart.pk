<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Forex\Models\Mt5Account;
use Tests\TestCase;

class ForexWatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_sent_to_login_from_market_watch(): void
    {
        $this->get('/forex/watch')->assertRedirect('/login');
    }

    public function test_market_watch_lists_symbols_without_trading(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('forex.watch'))
            ->assertOk()
            ->assertSeeInOrder([
                'href="'.route('forex.watch').'"',
                'Market Watch',
                'href="'.route('forex.market-watch').'"',
                'Trade Terminal',
            ], false)
            ->assertSee('EURUSD')
            ->assertSee('XAUUSD')
            ->assertSee('BTCUSD')
            ->assertSee('US30')
            ->assertSee('Waiting for connector')
            ->assertDontSee('Fast trading')
            ->assertDontSee('>Buy<', false)
            ->assertDontSee('>Sell<', false)
            ->assertDontSee(route('forex.positions.open'), false);
    }

    public function test_market_watch_shows_live_rates_from_the_connector(): void
    {
        $account = Mt5Account::query()->create([
            'name' => 'Pool A',
            'broker' => 'Demo Broker',
            'server' => 'Demo-Server',
            'login' => '501234',
            'active' => true,
            'connector_token' => 'watch-connector-token-1234567890',
        ]);

        $this->postJson('/api/forex/connector/sync', [
            'mt5_account_id' => (string) $account->id,
            'ticks' => [
                [
                    'symbol' => 'EURUSD',
                    'bid' => 1.0851,
                    'ask' => 1.0853,
                    'high' => 1.086,
                    'low' => 1.084,
                    'digits' => 5,
                    'time_msc' => (int) (microtime(true) * 1000),
                ],
            ],
        ], [
            'Authorization' => 'Bearer watch-connector-token-1234567890',
        ])->assertOk();

        $this->actingAs(User::factory()->create())
            ->get(route('forex.watch'))
            ->assertOk()
            ->assertSee('Live')
            ->assertSee('1.08510')
            ->assertSee('1.08530')
            ->assertSee('1.08600')
            ->assertSee('1.08400');
    }
}
