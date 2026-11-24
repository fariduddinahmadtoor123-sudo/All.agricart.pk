<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Forex\Models\Mt5Account;
use Modules\Forex\Models\Mt5Quote;
use Modules\Forex\Support\ConnectorPackage;
use Modules\Forex\Support\Symbols;
use Tests\TestCase;
use ZipArchive;

class ForexConnectorTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving_an_mt5_account_creates_a_connector_token(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('forex.mt5-accounts.store'), [
                '_form' => 'mt5',
                'name' => 'Pool A',
                'broker' => 'Demo Broker',
                'server' => 'Demo-Server',
                'login' => '501234',
            ])
            ->assertRedirect(route('forex.mt5-accounts'));

        $account = Mt5Account::query()->first();

        $this->assertNotNull($account);
        $this->assertNotEmpty($account->connector_token);
    }

    public function test_a_user_can_download_the_connector_package(): void
    {
        $user = User::factory()->create();
        $account = Mt5Account::query()->create([
            'name' => 'Pool A',
            'broker' => 'Demo Broker',
            'server' => 'Demo-Server',
            'login' => '501234',
            'active' => true,
        ]);

        $response = $this->actingAs($user)
            ->get(route('forex.mt5-accounts.connector', $account));

        $response->assertOk();
        $this->assertStringContainsString('attachment', (string) $response->headers->get('content-disposition'));
        $this->assertStringContainsString('desk-mt5-Pool-A-501234.zip', (string) $response->headers->get('content-disposition'));

        $temp = tempnam(sys_get_temp_dir(), 'deskzip');
        file_put_contents($temp, $response->streamedContent());

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($temp));
        $this->assertNotFalse($zip->locateName('start-mt5.bat'));
        $this->assertNotFalse($zip->locateName('mt5_bridge.py'));
        $this->assertNotFalse($zip->locateName('requirements.txt'));
        $this->assertNotFalse($zip->locateName('README.txt'));

        $bat = $zip->getFromName('start-mt5.bat');
        $this->assertIsString($bat);
        $this->assertStringContainsString('DESK_CONNECTOR_TOKEN='.$account->fresh()->connector_token, $bat);
        $this->assertStringContainsString('MT5_LOGIN=501234', $bat);
        $zip->close();
        unlink($temp);
    }

    public function test_the_connector_can_sync_quotes(): void
    {
        $account = Mt5Account::query()->create([
            'name' => 'Pool A',
            'broker' => 'Demo Broker',
            'server' => 'Demo-Server',
            'login' => '501234',
            'active' => true,
            'connector_token' => 'test-connector-token-1234567890',
        ]);

        $this->postJson('/api/forex/connector/sync', [
            'mt5_account_id' => (string) $account->id,
            'account' => [
                'login' => '501234',
                'balance' => 12500.55,
                'equity' => 12610.20,
                'margin' => 200.10,
                'free_margin' => 12410.10,
                'profit' => 109.65,
            ],
            'ticks' => [
                [
                    'symbol' => 'EURUSD',
                    'bid' => 1.0851,
                    'ask' => 1.0853,
                    'high' => 1.086,
                    'low' => 1.084,
                    'spread' => 0.0002,
                    'digits' => 5,
                    'volume_min' => 0.01,
                    'volume_step' => 0.01,
                    'time_msc' => (int) (microtime(true) * 1000),
                ],
                [
                    'symbol' => 'XAUUSD',
                    'bid' => 2650.1,
                    'ask' => 2650.4,
                    'high' => 2660,
                    'low' => 2640,
                    'spread' => 0.3,
                    'digits' => 2,
                    'volume_min' => 0.01,
                    'volume_step' => 0.01,
                    'time_msc' => (int) (microtime(true) * 1000),
                ],
                [
                    'symbol' => 'FAKECOIN',
                    'bid' => 9,
                    'ask' => 10,
                    'digits' => 2,
                    'time_msc' => (int) (microtime(true) * 1000),
                ],
            ],
        ], [
            'Authorization' => 'Bearer test-connector-token-1234567890',
        ])->assertOk()->assertJson([
            'ok' => true,
            'quotes' => 2,
            'watch_symbols' => Symbols::allowed(),
        ]);

        $fresh = $account->fresh();
        $this->assertNotNull($fresh->last_seen_at);
        $this->assertTrue($fresh->connectorIsOnline());
        $this->assertSame(12500.55, (float) $fresh->mt5_balance);
        $this->assertSame(12610.20, (float) $fresh->mt5_equity);
        $this->assertSame(2, Mt5Quote::query()->count());
        $this->assertSame(5, Mt5Quote::query()->where('symbol', 'EURUSD')->value('digits'));
        $this->assertSame(0.01, (float) Mt5Quote::query()->where('symbol', 'EURUSD')->value('volume_min'));

        $viewer = User::factory()->create();

        $this->actingAs($viewer)
            ->get(route('forex.market-watch'))
            ->assertOk()
            ->assertSee('1.08510')
            ->assertSee('2650.10')
            ->assertDontSee('10,000.00')
            ->assertSee('0.00')
            ->assertDontSee('12,610.20');

        $this->actingAs($viewer)
            ->getJson(route('forex.market-watch.quotes', ['ticks' => 1]))
            ->assertOk()
            ->assertJsonPath('quotes.0.symbol', fn ($symbol) => is_string($symbol))
            ->assertJsonMissingPath('orders');
    }

    public function test_connector_sync_rejects_a_bad_token(): void
    {
        $this->postJson('/api/forex/connector/sync', [
            'ticks' => [],
        ], [
            'Authorization' => 'Bearer wrong-token',
        ])->assertUnauthorized();
    }

    public function test_mt5_list_shows_download_for_each_account(): void
    {
        $user = User::factory()->create();
        Mt5Account::query()->create([
            'name' => 'Pool A',
            'broker' => 'Demo Broker',
            'server' => 'Demo-Server',
            'login' => '501234',
            'active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('forex.mt5-accounts'))
            ->assertOk()
            ->assertSee('Download connector')
            ->assertSee(route('forex.mt5-accounts.connector', Mt5Account::query()->first()), false)
            ->assertSee('Prepare on this PC')
            ->assertSee('Start connector')
            ->assertSee('Offline');
    }

    public function test_a_connector_can_be_prepared_locally_on_this_pc(): void
    {
        $user = User::factory()->create();
        $account = Mt5Account::query()->create([
            'name' => 'Pool A',
            'broker' => 'Demo Broker',
            'server' => 'Demo-Server',
            'login' => '501234',
            'active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('forex.mt5-accounts.connector.prepare', $account))
            ->assertRedirect(route('forex.mt5-accounts'))
            ->assertSessionHas('connector_ready');

        $bat = session('connector_ready');
        $this->assertIsString($bat);
        $this->assertFileExists($bat);
        $this->assertFileExists(dirname($bat).DIRECTORY_SEPARATOR.'mt5_bridge.py');
        $this->assertStringContainsString('laragon\\bin\\python', file_get_contents($bat));
    }

    public function test_a_customer_cannot_take_the_connector_or_the_ledgers(): void
    {
        $role = Role::query()->create([
            'name' => 'Customer',
            'description' => 'Trades only.',
        ]);
        $customer = User::factory()->create(['role_id' => $role->id]);
        $account = Mt5Account::query()->create([
            'name' => 'Pool A',
            'broker' => 'Demo Broker',
            'server' => 'Demo-Server',
            'login' => '501234',
            'active' => true,
        ]);

        $this->get(route('forex.mt5-accounts.connector', $account))
            ->assertRedirect('/login');

        $this->actingAs($customer)
            ->get(route('forex.mt5-accounts.connector', $account))
            ->assertForbidden();

        $this->actingAs($customer)
            ->get(route('forex.ledger'))
            ->assertForbidden();

        $this->actingAs($customer)
            ->get(route('forex.market-watch'))
            ->assertOk()
            ->assertDontSee('MT5 accounts');
    }

    public function test_the_package_detects_laragon_python(): void
    {
        $package = new ConnectorPackage;

        $this->assertNotNull($package->detectPython());
        $this->assertStringContainsString('laragon', strtolower((string) $package->detectPython()));
    }
}
