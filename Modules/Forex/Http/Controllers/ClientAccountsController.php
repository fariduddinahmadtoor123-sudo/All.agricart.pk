<?php

namespace Modules\Forex\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use Modules\Forex\Http\Requests\DepositClientAccountRequest;
use Modules\Forex\Http\Requests\StoreClientAccountRequest;
use Modules\Forex\Http\Requests\UpdateClientAccountRequest;
use Modules\Forex\Models\ClientAccount;
use Modules\Forex\Models\LedgerEntry;
use Modules\Forex\Models\Mt5Account;
use Modules\Forex\Support\Ledger;

class ClientAccountsController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('forex.client-accounts', [
            'user' => $request->user(),
            'accounts' => ClientAccount::query()
                ->with(['user', 'mt5Account'])
                ->latest('id')
                ->get(),
            'users' => User::query()
                ->whereDoesntHave('clientAccount')
                ->orderBy('name')
                ->get(),
            'mt5Accounts' => Mt5Account::query()->where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('forex.client-accounts.create', [
            'user' => $request->user(),
            'users' => User::query()
                ->whereDoesntHave('clientAccount')
                ->orderBy('name')
                ->get(),
            'mt5Accounts' => Mt5Account::query()->where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreClientAccountRequest $request): RedirectResponse
    {
        $account = ClientAccount::query()->create([
            'number' => ClientAccount::nextNumber(),
            'user_id' => $request->integer('user_id'),
            'mt5_account_id' => $request->validated('mt5_account_id'),
            'balance' => 0,
            'leverage' => $request->integer('leverage'),
            'active' => true,
        ]);

        $amount = round((float) $request->input('amount'), 2);

        if ($amount > 0) {
            app(Ledger::class)->post($account, [
                'type' => LedgerEntry::TYPE_DEPOSIT,
                'amount' => $amount,
                'note' => 'Opening balance',
            ], $request->user());
        }

        return redirect()->route('forex.client-accounts');
    }

    public function update(UpdateClientAccountRequest $request, ClientAccount $account): RedirectResponse
    {
        $account->update([
            'mt5_account_id' => $request->validated('mt5_account_id'),
            'leverage' => $request->integer('leverage'),
        ]);

        return redirect()->route('forex.client-accounts');
    }

    public function payment(DepositClientAccountRequest $request, ClientAccount $account, Ledger $ledger): RedirectResponse
    {
        try {
            $ledger->post($account, [
                'type' => LedgerEntry::TYPE_DEPOSIT,
                'amount' => $request->input('amount'),
                'note' => 'Payment',
            ], $request->user());
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->withErrors(['amount' => $exception->getMessage()]);
        }

        return redirect()->route('forex.client-accounts');
    }
}
