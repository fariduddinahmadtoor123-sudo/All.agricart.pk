<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Forex\Models\ClientAccount;
use Modules\Forex\Models\Mt5Account;
use Tests\TestCase;

class ForexAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_sent_to_login_from_account_pages(): void
    {
        $this->get('/forex/client-accounts')->assertRedirect('/login');
        $this->get('/forex/mt5-accounts')->assertRedirect('/login');
    }

    public function test_a_user_opens_mt5_and_client_account_pages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('forex.mt5-accounts'))
            ->assertOk()
            ->assertSee('MT5 accounts')
            ->assertSee('Add MT5 account')
            ->assertSee('No MT5 accounts yet.')
            ->assertSee('Trade Terminal')
            ->assertSee('Client accounts');

        $this->actingAs($user)
            ->get(route('forex.client-accounts'))
            ->assertOk()
            ->assertSee('Client accounts')
            ->assertSee('Open account')
            ->assertSee('No client accounts yet.')
            ->assertSee('MT5 accounts');
    }

    public function test_an_mt5_account_can_be_saved(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('forex.mt5-accounts.store'), [
                '_form' => 'mt5',
                'name' => 'Pool A',
                'broker' => 'Demo Broker',
                'company' => 'Retail desk',
                'server' => 'Demo-Server',
                'login' => '501234',
            ])
            ->assertRedirect(route('forex.mt5-accounts'));

        $this->assertDatabaseHas('forex_mt5_accounts', [
            'name' => 'Pool A',
            'broker' => 'Demo Broker',
            'company' => 'Retail desk',
            'login' => '501234',
        ]);
    }

    public function test_a_client_account_is_opened_for_an_existing_user(): void
    {
        $admin = User::factory()->create();
        $trader = User::factory()->create([
            'name' => 'Sara Trader',
            'email' => 'sara@example.com',
        ]);
        $mt5 = Mt5Account::query()->create([
            'name' => 'Pool A',
            'broker' => 'Demo Broker',
            'server' => 'Demo-Server',
            'login' => '501234',
            'active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('forex.client-accounts.store'), [
                '_form' => 'client',
                'user_id' => $trader->id,
                'mt5_account_id' => $mt5->id,
            ])
            ->assertRedirect(route('forex.client-accounts'));

        $account = ClientAccount::query()->first();

        $this->assertNotNull($account);
        $this->assertSame('TA-100001', $account->number);
        $this->assertSame($trader->id, $account->user_id);
        $this->assertSame($mt5->id, $account->mt5_account_id);

        $this->actingAs($admin)
            ->get(route('forex.client-accounts'))
            ->assertOk()
            ->assertSee('TA-100001')
            ->assertSee('Sara Trader')
            ->assertSee('sara@example.com')
            ->assertSee('Pool A');
    }

    public function test_a_client_account_can_move_to_another_mt5_account(): void
    {
        $admin = User::factory()->create();
        $trader = User::factory()->create();
        $first = Mt5Account::query()->create([
            'name' => 'Pool A',
            'broker' => 'Demo Broker',
            'server' => 'Demo-Server',
            'login' => '501234',
            'active' => true,
        ]);
        $second = Mt5Account::query()->create([
            'name' => 'Pool B',
            'broker' => 'Other Broker',
            'server' => 'Live-Server',
            'login' => '778899',
            'active' => true,
        ]);
        $account = ClientAccount::query()->create([
            'number' => 'TA-100001',
            'user_id' => $trader->id,
            'mt5_account_id' => $first->id,
            'active' => true,
        ]);

        $this->actingAs($admin)
            ->patch(route('forex.client-accounts.update', $account), [
                'mt5_account_id' => $second->id,
            ])
            ->assertRedirect(route('forex.client-accounts'));

        $this->assertSame($second->id, $account->fresh()->mt5_account_id);
    }

    public function test_the_same_user_cannot_get_two_client_accounts(): void
    {
        $admin = User::factory()->create();
        $trader = User::factory()->create();
        ClientAccount::query()->create([
            'number' => 'TA-100001',
            'user_id' => $trader->id,
            'active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('forex.client-accounts.store'), [
                '_form' => 'client',
                'user_id' => $trader->id,
            ])
            ->assertSessionHasErrors('user_id');
    }
}
