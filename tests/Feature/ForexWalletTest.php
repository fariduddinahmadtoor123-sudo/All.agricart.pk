<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Forex\Models\ClientAccount;
use Modules\Forex\Models\Payment;
use Tests\TestCase;

class ForexWalletTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_user_menu_opens_a_personal_wallet(): void
    {
        $trader = User::factory()->create(['name' => 'Sara Trader']);
        ClientAccount::query()->create([
            'number' => 'TA-100001',
            'user_id' => $trader->id,
            'balance' => 1000,
            'active' => true,
        ]);

        $this->actingAs($trader)
            ->get(route('personal-vault'))
            ->assertOk()
            ->assertSee('Forex Wallet')
            ->assertSee('Trade Terminal')
            ->assertSee('Sara Trader')
            ->assertSee('To wallet')
            ->assertSee('To account')
            ->assertSee('Request withdrawal')
            ->assertSee('TA-100001');
    }

    public function test_a_customer_moves_money_between_the_account_and_the_wallet(): void
    {
        $trader = User::factory()->create();
        $account = ClientAccount::query()->create([
            'number' => 'TA-100001',
            'user_id' => $trader->id,
            'balance' => 1000,
            'active' => true,
        ]);

        $this->actingAs($trader)
            ->post(route('personal-vault.to-wallet'), ['amount' => '250'])
            ->assertRedirect(route('personal-vault'));

        $this->assertSame(750.0, (float) $account->fresh()->balance);
        $this->assertSame(250.0, (float) $trader->wallet()->first()->balance);

        $this->actingAs($trader)
            ->post(route('personal-vault.to-account'), ['amount' => '50'])
            ->assertRedirect(route('personal-vault'));

        $this->assertSame(800.0, (float) $account->fresh()->balance);
        $this->assertSame(200.0, (float) $trader->wallet()->first()->balance);

        $this->actingAs($trader)
            ->get(route('personal-vault'))
            ->assertOk()
            ->assertSee('Account')
            ->assertSee('Wallet')
            ->assertSee('+250.00')
            ->assertSee('-50.00');
    }

    public function test_a_move_into_the_account_rejects_an_empty_wallet(): void
    {
        $trader = User::factory()->create();
        $account = ClientAccount::query()->create([
            'number' => 'TA-100001',
            'user_id' => $trader->id,
            'balance' => 1000,
            'active' => true,
        ]);

        $this->actingAs($trader)
            ->from(route('personal-vault'))
            ->post(route('personal-vault.to-account'), ['amount' => '10'])
            ->assertRedirect(route('personal-vault'))
            ->assertSessionHasErrors('amount');

        $this->assertSame(1000.0, (float) $account->fresh()->balance);
    }

    public function test_a_staff_deposit_and_an_approved_withdrawal_show_from_and_to_on_the_ledger(): void
    {
        $admin = User::factory()->create();
        $trader = User::factory()->create(['name' => 'Sara Trader']);
        $account = ClientAccount::query()->create([
            'number' => 'TA-100001',
            'user_id' => $trader->id,
            'balance' => 1000,
            'active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('forex.ledger.wallet', $account), ['amount' => '80'])
            ->assertRedirect(route('forex.ledger.show', $account));

        $this->assertSame(80.0, (float) $trader->wallet()->first()->balance);
        $this->assertSame(1000.0, (float) $account->fresh()->balance);

        $this->actingAs($trader)
            ->post(route('personal-vault.withdraw'), ['amount' => '30'])
            ->assertRedirect(route('personal-vault'));

        $payment = Payment::query()->where('status', 'pending')->first();
        $this->assertNotNull($payment);

        $this->actingAs($admin)
            ->post(route('forex.payments.approve', $payment))
            ->assertRedirect();

        $this->assertSame(50.0, (float) $trader->wallet()->first()->balance);
        $this->assertSame(1000.0, (float) $account->fresh()->balance);

        $this->actingAs($admin)
            ->get(route('forex.ledger.show', $account))
            ->assertOk()
            ->assertSee('From')
            ->assertSee('To')
            ->assertSee('Desk')
            ->assertSee('Wallet')
            ->assertSee('Deposit to wallet');

        $this->actingAs($admin)
            ->get(route('forex.ledger'))
            ->assertOk()
            ->assertSee('Wallet')
            ->assertSee('50.00');
    }
}
