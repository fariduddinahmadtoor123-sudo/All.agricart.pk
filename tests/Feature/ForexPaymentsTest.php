<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Forex\Models\ClientAccount;
use Modules\Forex\Models\LedgerEntry;
use Modules\Forex\Models\Payment;
use Tests\TestCase;

class ForexPaymentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_payments_page_lists_the_cashier_queue(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('forex.payments'))
            ->assertOk()
            ->assertSee('Forex Pending Payments')
            ->assertSee('Add request')
            ->assertSee('Pending')
            ->assertSee('History')
            ->assertSee('Rejected')
            ->assertSee('No requests waiting.')
            ->assertDontSee('Mark paid');
    }

    public function test_a_deposit_request_stays_pending_until_it_is_approved(): void
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
            ->post(route('forex.payments.store'), [
                '_form' => 'payment',
                'client_account_id' => $account->id,
                'type' => 'deposit',
                'amount' => '100',
                'reference' => 'SLIP-100',
            ])
            ->assertRedirect(route('forex.payments', ['status' => 'pending']));

        $this->assertSame(1000.0, (float) $account->fresh()->balance);
        $this->assertSame(0, LedgerEntry::query()->count());

        $payment = Payment::query()->first();
        $this->assertNotNull($payment);
        $this->assertSame('pending', $payment->status);
        $this->assertSame('SLIP-100', $payment->reference);
        $this->assertNotNull($payment->created_at);

        $this->actingAs($admin)
            ->get(route('forex.payments'))
            ->assertOk()
            ->assertSee('TA-100001')
            ->assertSee('Sara Trader')
            ->assertSee('Deposit')
            ->assertSee('+100.00')
            ->assertSee('SLIP-100')
            ->assertSee('Approve');

        $this->actingAs($admin)
            ->post(route('forex.payments.approve', $payment))
            ->assertRedirect(route('forex.payments', ['status' => 'pending']));

        $this->assertSame(1100.0, (float) $account->fresh()->balance);
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame($admin->id, $payment->fresh()->decided_by);

        $entry = LedgerEntry::query()->first();
        $this->assertNotNull($entry);
        $this->assertSame('deposit', $entry->type);
        $this->assertSame(100.0, (float) $entry->amount);
        $this->assertSame('SLIP-100', $entry->note);

        $this->actingAs($admin)
            ->get(route('forex.payments', ['status' => 'history']))
            ->assertOk()
            ->assertSee('SLIP-100')
            ->assertSee('Sara Trader')
            ->assertSee('+100.00');
    }

    public function test_an_approved_withdrawal_reduces_the_ledger_balance(): void
    {
        $admin = User::factory()->create();
        $trader = User::factory()->create();
        $account = ClientAccount::query()->create([
            'number' => 'TA-100001',
            'user_id' => $trader->id,
            'balance' => 1002.18,
            'active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('forex.payments.store'), [
                'client_account_id' => $account->id,
                'type' => 'withdrawal',
                'amount' => '100',
                'reference' => 'WD-100',
            ])
            ->assertRedirect(route('forex.payments', ['status' => 'pending']));

        $payment = Payment::query()->first();
        $this->assertSame(1002.18, (float) $account->fresh()->balance);

        $this->actingAs($admin)
            ->post(route('forex.payments.approve', $payment))
            ->assertRedirect(route('forex.payments', ['status' => 'pending']));

        $this->assertSame(902.18, (float) $account->fresh()->balance);

        $entry = LedgerEntry::query()->first();
        $this->assertNotNull($entry);
        $this->assertSame('withdrawal', $entry->type);
        $this->assertSame(-100.0, (float) $entry->amount);
        $this->assertSame(902.18, (float) $entry->balance_after);
        $this->assertSame('WD-100', $entry->note);
    }

    public function test_a_withdrawal_that_would_overdraw_stays_pending(): void
    {
        $admin = User::factory()->create();
        $trader = User::factory()->create();
        $account = ClientAccount::query()->create([
            'number' => 'TA-100001',
            'user_id' => $trader->id,
            'balance' => 100,
            'active' => true,
        ]);
        $payment = Payment::query()->create([
            'client_account_id' => $account->id,
            'type' => 'withdrawal',
            'amount' => 250,
            'status' => 'pending',
            'reference' => 'WD-250',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->from(route('forex.payments'))
            ->post(route('forex.payments.approve', $payment))
            ->assertRedirect(route('forex.payments'))
            ->assertSessionHasErrors('payment');

        $this->assertSame(100.0, (float) $account->fresh()->balance);
        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertSame(0, LedgerEntry::query()->count());
    }

    public function test_rejecting_a_request_keeps_the_reason_and_the_balance(): void
    {
        $admin = User::factory()->create();
        $trader = User::factory()->create();
        $account = ClientAccount::query()->create([
            'number' => 'TA-100001',
            'user_id' => $trader->id,
            'balance' => 1000,
            'active' => true,
        ]);
        $payment = Payment::query()->create([
            'client_account_id' => $account->id,
            'type' => 'deposit',
            'amount' => 500,
            'status' => 'pending',
            'reference' => 'SLIP-500',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->from(route('forex.payments'))
            ->post(route('forex.payments.reject', $payment), [
                '_form' => 'reject',
                'payment_id' => $payment->id,
                'reason' => 'Slip amount does not match.',
            ])
            ->assertRedirect(route('forex.payments', ['status' => 'pending']));

        $this->assertSame(1000.0, (float) $account->fresh()->balance);
        $this->assertSame('rejected', $payment->fresh()->status);
        $this->assertSame('Slip amount does not match.', $payment->fresh()->rejection_reason);
        $this->assertSame(0, LedgerEntry::query()->count());

        $this->actingAs($admin)
            ->get(route('forex.payments', ['status' => 'rejected']))
            ->assertOk()
            ->assertSee('SLIP-500')
            ->assertSee('Slip amount does not match.')
            ->assertSee('+500.00');
    }

    public function test_a_customer_moves_a_withdrawal_into_the_wallet_from_the_terminal(): void
    {
        $trader = User::factory()->create(['name' => 'Sara Trader']);
        $account = ClientAccount::query()->create([
            'number' => 'TA-100001',
            'user_id' => $trader->id,
            'balance' => 1000,
            'active' => true,
        ]);
        LedgerEntry::query()->create([
            'client_account_id' => $account->id,
            'type' => 'trade',
            'amount' => 2.18,
            'balance_after' => 1002.18,
            'desk_ticket' => 'D-100001',
            'mt5_ticket' => '2293500973',
            'note' => 'Close D-100001 buy XAUUSD 0.01 lots',
        ]);

        $this->actingAs($trader)
            ->post(route('forex.market-watch.payments.store'), [
                '_form' => 'terminal-withdrawal',
                'type' => 'withdrawal',
                'amount' => '100',
            ])
            ->assertRedirect(route('forex.market-watch'));

        $this->assertSame(900.0, (float) $account->fresh()->balance);
        $this->assertSame(100.0, (float) $trader->wallet()->first()->balance);
        $this->assertSame(0, Payment::query()->count());

        $this->actingAs($trader)
            ->post(route('personal-vault.withdraw'), [
                'amount' => '40',
            ])
            ->assertRedirect(route('personal-vault'));

        $payment = Payment::query()->first();
        $this->assertNotNull($payment);
        $this->assertSame('pending', $payment->status);
        $this->assertSame('wallet', $payment->book);
        $this->assertNull($payment->reference);
        $this->assertSame(100.0, (float) $trader->wallet()->first()->balance);

        $this->actingAs($trader)
            ->get(route('forex.market-watch'))
            ->assertOk()
            ->assertSee('Deposit')
            ->assertSee('Withdrawal')
            ->assertDontSee('Transfer or receipt number');

        $this->actingAs($trader)
            ->get(route('forex.market-watch.ledger'))
            ->assertOk()
            ->assertSee('Profit')
            ->assertSee('+2.18')
            ->assertSee('Pending')
            ->assertDontSee('D-100001')
            ->assertDontSee('2293500973');
    }

    public function test_a_rejection_without_a_reason_stays_pending(): void
    {
        $admin = User::factory()->create();
        $trader = User::factory()->create();
        $account = ClientAccount::query()->create([
            'number' => 'TA-100001',
            'user_id' => $trader->id,
            'balance' => 1000,
            'active' => true,
        ]);
        $payment = Payment::query()->create([
            'client_account_id' => $account->id,
            'type' => 'deposit',
            'amount' => 500,
            'status' => 'pending',
            'reference' => 'SLIP-500',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->from(route('forex.payments'))
            ->post(route('forex.payments.reject', $payment), [
                'reason' => '   ',
            ])
            ->assertRedirect(route('forex.payments'))
            ->assertSessionHasErrors('reason');

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertSame(1000.0, (float) $account->fresh()->balance);
    }
}
