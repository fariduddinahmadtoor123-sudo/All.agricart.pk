<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Forex\Models\ClientAccount;
use Modules\Forex\Models\LedgerEntry;
use Modules\Forex\Support\MarketWatch;
use Tests\TestCase;

class ForexLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_opens_the_ledger_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('forex.ledger'))
            ->assertOk()
            ->assertSee('Ledger')
            ->assertSee('Post entry')
            ->assertSee('Balances')
            ->assertSee('Recent entries')
            ->assertSee('Client accounts')
            ->assertSee('MT5 accounts');
    }

    public function test_a_deposit_updates_the_client_balance(): void
    {
        $admin = User::factory()->create();
        $trader = User::factory()->create(['name' => 'Sara Trader']);
        $account = ClientAccount::query()->create([
            'number' => 'TA-100001',
            'user_id' => $trader->id,
            'balance' => 0,
            'active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('forex.ledger.store'), [
                '_form' => 'ledger',
                'client_account_id' => $account->id,
                'type' => 'deposit',
                'amount' => '1500.50',
                'note' => 'Opening funds',
            ])
            ->assertRedirect(route('forex.ledger'));

        $this->assertSame(1500.5, (float) $account->fresh()->balance);

        $entry = LedgerEntry::query()->first();
        $this->assertNotNull($entry);
        $this->assertSame('deposit', $entry->type);
        $this->assertSame(1500.5, (float) $entry->amount);
        $this->assertSame(1500.5, (float) $entry->balance_after);
        $this->assertSame('Opening funds', $entry->note);
        $this->assertSame($admin->id, $entry->created_by);

        $this->actingAs($admin)
            ->get(route('forex.ledger'))
            ->assertOk()
            ->assertSee('TA-100001')
            ->assertSee('1,500.50')
            ->assertSee('Deposit')
            ->assertSee('Opening funds');
    }

    public function test_a_withdrawal_cannot_overdraw_the_account(): void
    {
        $admin = User::factory()->create();
        $trader = User::factory()->create();
        $account = ClientAccount::query()->create([
            'number' => 'TA-100001',
            'user_id' => $trader->id,
            'balance' => 100,
            'active' => true,
        ]);

        $this->actingAs($admin)
            ->from(route('forex.ledger'))
            ->post(route('forex.ledger.store'), [
                '_form' => 'ledger',
                'client_account_id' => $account->id,
                'type' => 'withdrawal',
                'amount' => '250',
            ])
            ->assertRedirect(route('forex.ledger'))
            ->assertSessionHasErrors('amount');

        $this->assertSame(100.0, (float) $account->fresh()->balance);
        $this->assertSame(0, LedgerEntry::query()->count());
    }

    public function test_trade_terminal_uses_the_client_ledger_balance(): void
    {
        $trader = User::factory()->create();
        ClientAccount::query()->create([
            'number' => 'TA-100001',
            'user_id' => $trader->id,
            'balance' => 2500,
            'active' => true,
        ]);

        $this->assertSame(2500.0, MarketWatch::balanceFor($trader->fresh()));

        $this->actingAs($trader)
            ->get(route('forex.market-watch'))
            ->assertOk()
            ->assertSee('2,500.00');
    }
}
