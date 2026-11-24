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
            ->assertSee('Forex Customer Ledgers')
            ->assertDontSee('Post entry')
            ->assertDontSee('Accounts')
            ->assertDontSee('>Account<', false)
            ->assertSee('Opening balance')
            ->assertSee('Profit')
            ->assertDontSee('Recent entries')
            ->assertSee('Client accounts')
            ->assertSee('MT5 accounts')
            ->assertSee('Forex Pending Payments');
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
            ->assertRedirect(route('forex.ledger.show', $account));

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
            ->assertDontSee('Opening funds');

        $this->actingAs($admin)
            ->get(route('forex.ledger.show', $account))
            ->assertOk()
            ->assertSee('TA-100001')
            ->assertSee('Deposit')
            ->assertSee('Opening funds')
            ->assertSee('1,500.50');
    }

    public function test_a_trade_open_and_close_share_one_ledger_line(): void
    {
        $admin = User::factory()->create();
        $trader = User::factory()->create();
        $account = ClientAccount::query()->create([
            'number' => 'TA-100001',
            'user_id' => $trader->id,
            'balance' => 1002.18,
            'active' => true,
        ]);

        $open = LedgerEntry::query()->create([
            'client_account_id' => $account->id,
            'type' => 'trade',
            'amount' => 0,
            'balance_after' => 1000,
            'note' => 'Open D-100001 buy XAUUSD 0.01 lots',
            'desk_ticket' => 'D-100001',
            'mt5_ticket' => '2293500973',
        ]);
        $open->forceFill(['created_at' => '2026-11-24 12:26:00', 'updated_at' => '2026-11-24 12:26:00'])->save();

        $close = LedgerEntry::query()->create([
            'client_account_id' => $account->id,
            'type' => 'trade',
            'amount' => 2.18,
            'balance_after' => 1002.18,
            'note' => 'Close D-100001 buy XAUUSD 0.01 lots',
            'desk_ticket' => 'D-100001',
            'mt5_ticket' => '2293500973',
        ]);
        $close->forceFill(['created_at' => '2026-11-24 12:28:00', 'updated_at' => '2026-11-24 12:28:00'])->save();

        $response = $this->actingAs($admin)
            ->get(route('forex.ledger.show', $account))
            ->assertOk()
            ->assertSee('D-100001')
            ->assertSee('2293500973')
            ->assertSee('24 Nov 12:26')
            ->assertSee('24 Nov 12:28')
            ->assertSee('2.18')
            ->assertSee('buy XAUUSD 0.01 lots')
            ->assertDontSee('Open D-100001')
            ->assertDontSee('Close D-100001');

        $this->assertSame(1, substr_count($response->getContent(), 'D-100001'));
        $this->assertSame(1, substr_count($response->getContent(), '2293500973'));
    }

    public function test_the_ledger_list_shows_opening_balance_and_signed_profit(): void
    {
        $admin = User::factory()->create();
        $winner = User::factory()->create();
        $loser = User::factory()->create();
        $up = ClientAccount::query()->create([
            'number' => 'TA-100001',
            'user_id' => $winner->id,
            'balance' => 1002.18,
            'active' => true,
        ]);
        $down = ClientAccount::query()->create([
            'number' => 'TA-100002',
            'user_id' => $loser->id,
            'balance' => 496,
            'active' => true,
        ]);

        LedgerEntry::query()->create([
            'client_account_id' => $up->id,
            'type' => 'deposit',
            'amount' => 1000,
            'balance_after' => 1000,
            'note' => 'Opening balance',
        ]);
        LedgerEntry::query()->create([
            'client_account_id' => $up->id,
            'type' => 'trade',
            'amount' => 2.18,
            'balance_after' => 1002.18,
            'desk_ticket' => 'D-100001',
            'mt5_ticket' => '2293500973',
            'note' => 'Close D-100001 buy XAUUSD 0.01 lots',
        ]);
        LedgerEntry::query()->create([
            'client_account_id' => $down->id,
            'type' => 'deposit',
            'amount' => 500,
            'balance_after' => 500,
            'note' => 'Opening balance',
        ]);
        LedgerEntry::query()->create([
            'client_account_id' => $down->id,
            'type' => 'trade',
            'amount' => -4,
            'balance_after' => 496,
            'desk_ticket' => 'D-100002',
            'mt5_ticket' => '2293501000',
            'note' => 'Close D-100002 sell EURUSD 0.01 lots',
        ]);

        $this->actingAs($admin)
            ->get(route('forex.ledger'))
            ->assertOk()
            ->assertSeeInOrder(['TA-100001', '1,000.00', '+2.18', '1,002.18'])
            ->assertSeeInOrder(['TA-100002', '500.00', '-4.00', '496.00']);
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

    public function test_a_withdrawal_pays_cash_out_of_the_ledger(): void
    {
        $admin = User::factory()->create();
        $trader = User::factory()->create(['name' => 'Sara Trader']);
        $account = ClientAccount::query()->create([
            'number' => 'TA-100001',
            'user_id' => $trader->id,
            'balance' => 1002.18,
            'active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('forex.ledger.store'), [
                '_form' => 'withdrawal-'.$account->id,
                'client_account_id' => $account->id,
                'type' => 'withdrawal',
                'amount' => '100',
                'note' => 'Client withdrawal',
            ])
            ->assertRedirect(route('forex.ledger.show', $account));

        $this->assertSame(902.18, (float) $account->fresh()->balance);

        $entry = LedgerEntry::query()->first();
        $this->assertNotNull($entry);
        $this->assertSame('withdrawal', $entry->type);
        $this->assertSame(-100.0, (float) $entry->amount);
        $this->assertSame(902.18, (float) $entry->balance_after);

        $this->actingAs($admin)
            ->get(route('forex.ledger.show', $account))
            ->assertOk()
            ->assertSee('Withdrawal')
            ->assertSee('Client withdrawal')
            ->assertSee('-100.00');
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
