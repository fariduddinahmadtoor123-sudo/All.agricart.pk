<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Forex\Models\ClientAccount;
use Modules\Forex\Models\Mt5Account;
use Modules\Forex\Models\Mt5Command;
use Modules\Forex\Models\Position;
use Modules\Forex\Models\WatchlistSymbol;
use Tests\TestCase;

class ForexMt5ExecutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_open_desk_trade_is_handed_to_the_linked_mt5_and_the_client_sees_equity(): void
    {
        $user = User::factory()->create();
        $mt5 = Mt5Account::query()->create([
            'name' => 'Pool',
            'broker' => 'Demo',
            'server' => 'Demo',
            'login' => '501234',
            'active' => true,
            'connector_token' => 'test-connector-token-1234567890',
        ]);
        $client = ClientAccount::query()->create([
            'number' => 'TA-100001',
            'user_id' => $user->id,
            'mt5_account_id' => $mt5->id,
            'balance' => 10,
            'active' => true,
        ]);
        WatchlistSymbol::query()->create([
            'user_id' => $user->id,
            'symbol' => 'BTCUSD',
            'sort' => 0,
        ]);
        Position::query()->create([
            'desk_ticket' => 'D-100001',
            'user_id' => $user->id,
            'client_account_id' => $client->id,
            'symbol' => 'BTCUSD',
            'side' => 'buy',
            'status' => Position::STATUS_OPEN,
            'order_type' => 'market',
            'quantity' => 0.01,
            'entry_price' => 100,
            'profit' => 0,
        ]);

        $sync = $this->postJson('/api/forex/connector/sync', [
            'mt5_account_id' => (string) $mt5->id,
            'account' => [
                'login' => '501234',
                'balance' => 33460.94,
                'equity' => 33460.94,
            ],
            'ticks' => [[
                'symbol' => 'EURUSD',
                'bid' => 1.1,
                'ask' => 1.1002,
                'digits' => 5,
                'time_msc' => (int) (microtime(true) * 1000),
            ]],
        ], [
            'Authorization' => 'Bearer test-connector-token-1234567890',
        ]);

        $sync->assertOk()
            ->assertJsonPath('commands.0.action', 'open')
            ->assertJsonPath('commands.0.desk_ticket', 'D-100001')
            ->assertJsonPath('commands.0.symbol', 'BTCUSD');

        $symbols = $sync->json('watch_symbols');
        $this->assertIsArray($symbols);
        $this->assertSame('BTCUSD', $symbols[0]);
        $this->assertContains('EURUSD', $symbols);

        $this->actingAs($user)
            ->get(route('forex.market-watch'))
            ->assertOk()
            ->assertSee('TA-100001')
            ->assertSee('10.00')
            ->assertSee('D-100001')
            ->assertDontSee('MT5 pending');

        $this->postJson('/api/forex/connector/executions', [
            'mt5_account_id' => (string) $mt5->id,
            'results' => [[
                'id' => $sync->json('commands.0.id'),
                'ok' => true,
                'mt5_ticket' => '88991',
                'price' => 100.25,
            ]],
        ], [
            'Authorization' => 'Bearer test-connector-token-1234567890',
        ])->assertOk();

        $position = Position::query()->first();
        $this->assertSame('88991', $position->mt5_ticket);
        $this->assertSame(100.25, (float) $position->entry_price);
        $this->assertSame(Mt5Command::STATUS_DONE, Mt5Command::query()->first()->status);

        $this->actingAs($user)
            ->get(route('forex.market-watch'))
            ->assertOk()
            ->assertSee('D-100001')
            ->assertDontSee('MT5 88991');

        $this->assertDatabaseHas('forex_ledger_entries', [
            'client_account_id' => $client->id,
            'desk_ticket' => 'D-100001',
            'mt5_ticket' => '88991',
        ]);

        $this->actingAs($user)
            ->get(route('forex.ledger.show', $client))
            ->assertOk()
            ->assertSee('D-100001')
            ->assertSee('88991');
    }

    public function test_a_rejected_mt5_order_stays_on_the_desk_ticket(): void
    {
        $user = User::factory()->create();
        $mt5 = Mt5Account::query()->create([
            'name' => 'Pool',
            'broker' => 'Demo',
            'server' => 'Demo',
            'login' => '501234',
            'active' => true,
            'connector_token' => 'test-connector-token-1234567890',
        ]);
        $client = ClientAccount::query()->create([
            'number' => 'TA-100002',
            'user_id' => $user->id,
            'mt5_account_id' => $mt5->id,
            'balance' => 10,
            'active' => true,
        ]);
        $position = Position::query()->create([
            'desk_ticket' => 'D-100002',
            'user_id' => $user->id,
            'client_account_id' => $client->id,
            'symbol' => 'EURUSD',
            'side' => 'buy',
            'status' => Position::STATUS_OPEN,
            'order_type' => 'market',
            'quantity' => 0.01,
            'entry_price' => 1.1,
            'profit' => 0,
        ]);

        $sync = $this->postJson('/api/forex/connector/sync', [
            'mt5_account_id' => (string) $mt5->id,
            'ticks' => [[
                'symbol' => 'EURUSD',
                'bid' => 1.1,
                'ask' => 1.1002,
                'digits' => 5,
            ]],
        ], [
            'Authorization' => 'Bearer test-connector-token-1234567890',
        ])->assertOk();

        $this->postJson('/api/forex/connector/executions', [
            'mt5_account_id' => (string) $mt5->id,
            'results' => [[
                'id' => $sync->json('commands.0.id'),
                'ok' => false,
                'error' => 'Invalid volume',
            ]],
        ], [
            'Authorization' => 'Bearer test-connector-token-1234567890',
        ])->assertOk();

        $this->assertNull($position->fresh()->mt5_ticket);
        $command = Mt5Command::query()->first();
        $this->assertSame(Mt5Command::STATUS_FAILED, $command->status);
        $this->assertSame(1, $command->attempts);
        $this->assertSame('Invalid volume', $command->error);

        $this->postJson('/api/forex/connector/sync', [
            'mt5_account_id' => (string) $mt5->id,
            'ticks' => [[
                'symbol' => 'EURUSD',
                'bid' => 1.1,
                'ask' => 1.1002,
                'digits' => 5,
            ]],
        ], [
            'Authorization' => 'Bearer test-connector-token-1234567890',
        ])->assertOk();

        $this->assertSame(1, Mt5Command::query()->count());
    }

    public function test_a_close_is_waiting_on_the_fast_command_lane(): void
    {
        $mt5 = Mt5Account::query()->create([
            'name' => 'Pool',
            'broker' => 'Demo',
            'server' => 'Demo',
            'login' => '501234',
            'active' => true,
            'connector_token' => 'test-connector-token-1234567890',
        ]);
        $user = User::factory()->create();
        $client = ClientAccount::query()->create([
            'number' => 'TA-100008',
            'user_id' => $user->id,
            'mt5_account_id' => $mt5->id,
            'balance' => 100,
            'active' => true,
        ]);
        $position = Position::query()->create([
            'desk_ticket' => 'D-100008',
            'mt5_ticket' => '2848720352',
            'user_id' => $user->id,
            'client_account_id' => $client->id,
            'symbol' => 'XAUUSD',
            'side' => 'buy',
            'status' => Position::STATUS_CLOSING,
            'order_type' => 'market',
            'quantity' => 0.01,
            'entry_price' => 3668,
            'profit' => 1,
        ]);
        Mt5Command::query()->create([
            'mt5_account_id' => $mt5->id,
            'position_id' => $position->id,
            'action' => 'close',
            'status' => Mt5Command::STATUS_PENDING,
        ]);

        $this->postJson('/api/forex/connector/commands', [
            'mt5_account_id' => (string) $mt5->id,
        ], [
            'Authorization' => 'Bearer test-connector-token-1234567890',
        ])
            ->assertOk()
            ->assertJsonPath('commands.0.action', 'close')
            ->assertJsonPath('commands.0.mt5_ticket', '2848720352')
            ->assertJsonPath('commands.0.desk_ticket', 'D-100008');
    }

    public function test_a_filled_pending_order_becomes_an_open_position(): void
    {
        $mt5 = Mt5Account::query()->create([
            'name' => 'Pool',
            'broker' => 'Demo',
            'server' => 'Demo',
            'login' => '501234',
            'active' => true,
            'connector_token' => 'test-connector-token-1234567890',
        ]);
        $user = User::factory()->create();
        $client = ClientAccount::query()->create([
            'number' => 'TA-100009',
            'user_id' => $user->id,
            'mt5_account_id' => $mt5->id,
            'balance' => 100,
            'active' => true,
        ]);
        $position = Position::query()->create([
            'desk_ticket' => 'D-100015',
            'mt5_ticket' => '2848804899',
            'user_id' => $user->id,
            'client_account_id' => $client->id,
            'symbol' => 'XAUUSD',
            'side' => 'sell',
            'status' => Position::STATUS_PENDING,
            'order_type' => 'sell_limit',
            'quantity' => 0.01,
            'trigger_price' => 3674,
            'profit' => 0,
        ]);

        $this->postJson('/api/forex/connector/executions', [
            'mt5_account_id' => (string) $mt5->id,
            'results' => [[
                'position_id' => $position->id,
                'ok' => true,
                'filled' => true,
                'mt5_ticket' => '2848805001',
                'price' => 3674,
            ]],
        ], [
            'Authorization' => 'Bearer test-connector-token-1234567890',
        ])->assertOk();

        $position->refresh();
        $this->assertSame(Position::STATUS_OPEN, $position->status);
        $this->assertSame('2848805001', $position->mt5_ticket);
        $this->assertSame(3674.0, (float) $position->entry_price);
    }

    public function test_a_pending_order_stays_until_mt5_confirms_the_cancel(): void
    {
        $mt5 = Mt5Account::query()->create([
            'name' => 'Pool',
            'broker' => 'Demo',
            'server' => 'Demo',
            'login' => '501235',
            'active' => true,
            'connector_token' => 'test-connector-token-1234567890',
        ]);
        $user = User::factory()->create();
        $client = ClientAccount::query()->create([
            'number' => 'TA-100010',
            'user_id' => $user->id,
            'mt5_account_id' => $mt5->id,
            'balance' => 100,
            'active' => true,
        ]);
        $position = Position::query()->create([
            'desk_ticket' => 'D-100016',
            'mt5_ticket' => '2848806000',
            'user_id' => $user->id,
            'client_account_id' => $client->id,
            'symbol' => 'XAUUSD',
            'side' => 'sell',
            'status' => Position::STATUS_PENDING,
            'order_type' => 'sell_limit',
            'quantity' => 0.01,
            'trigger_price' => 3700,
            'profit' => 0,
        ]);

        $this->actingAs($user)
            ->post(route('forex.orders.cancel', $position))
            ->assertRedirect();

        $position->refresh();
        $this->assertSame(Position::STATUS_PENDING, $position->status);
        $this->assertNull($position->closed_at);

        $command = Mt5Command::query()->where('position_id', $position->id)->where('action', 'cancel')->first();
        $this->assertNotNull($command);

        $this->postJson('/api/forex/connector/executions', [
            'mt5_account_id' => (string) $mt5->id,
            'results' => [[
                'id' => $command->id,
                'ok' => true,
                'mt5_ticket' => '2848806000',
            ]],
        ], [
            'Authorization' => 'Bearer test-connector-token-1234567890',
        ])->assertOk();

        $position->refresh();
        $client->refresh();
        $this->assertSame(Position::STATUS_CANCELLED, $position->status);
        $this->assertNotNull($position->closed_at);
        $this->assertSame(0.0, (float) $position->profit);
        $this->assertSame(100.0, (float) $client->balance);
    }

    public function test_a_rejected_open_is_not_sent_again_after_five_attempts(): void
    {
        $mt5 = Mt5Account::query()->create([
            'name' => 'Pool',
            'broker' => 'Demo',
            'server' => 'Demo',
            'login' => '501236',
            'active' => true,
            'connector_token' => 'test-connector-token-1234567890',
        ]);
        $user = User::factory()->create();
        $client = ClientAccount::query()->create([
            'number' => 'TA-100011',
            'user_id' => $user->id,
            'mt5_account_id' => $mt5->id,
            'balance' => 100,
            'active' => true,
        ]);
        $position = Position::query()->create([
            'desk_ticket' => 'D-100017',
            'user_id' => $user->id,
            'client_account_id' => $client->id,
            'symbol' => 'EURUSD',
            'side' => 'buy',
            'status' => Position::STATUS_OPEN,
            'order_type' => 'market',
            'quantity' => 0.01,
            'entry_price' => 1.1,
            'profit' => 0,
        ]);
        Mt5Command::query()->create([
            'mt5_account_id' => $mt5->id,
            'position_id' => $position->id,
            'action' => 'open',
            'status' => Mt5Command::STATUS_FAILED,
            'attempts' => 5,
            'error' => '10026 AutoTrading disabled by server',
        ]);

        $this->postJson('/api/forex/connector/sync', [
            'mt5_account_id' => (string) $mt5->id,
            'account' => ['login' => '501236', 'balance' => 100, 'equity' => 100],
            'ticks' => [[
                'symbol' => 'EURUSD',
                'bid' => 1.1,
                'ask' => 1.1002,
                'digits' => 5,
                'time_msc' => (int) (microtime(true) * 1000),
            ]],
        ], [
            'Authorization' => 'Bearer test-connector-token-1234567890',
        ])->assertOk()->assertJsonPath('commands', []);

        $this->assertSame(1, Mt5Command::query()->where('position_id', $position->id)->count());
    }

    public function test_execution_results_reject_a_bad_token(): void
    {
        $this->postJson('/api/forex/connector/executions', [
            'results' => [],
        ], [
            'Authorization' => 'Bearer wrong-token',
        ])->assertUnauthorized();
    }
}
