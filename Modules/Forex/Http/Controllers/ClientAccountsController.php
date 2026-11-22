<?php

namespace Modules\Forex\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Forex\Http\Requests\StoreClientAccountRequest;
use Modules\Forex\Http\Requests\UpdateClientAccountRequest;
use Modules\Forex\Models\ClientAccount;
use Modules\Forex\Models\Mt5Account;

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
        ClientAccount::query()->create([
            'number' => ClientAccount::nextNumber(),
            'user_id' => $request->integer('user_id'),
            'mt5_account_id' => $request->validated('mt5_account_id'),
            'balance' => 0,
            'active' => true,
        ]);

        return redirect()->route('forex.client-accounts');
    }

    public function update(UpdateClientAccountRequest $request, ClientAccount $account): RedirectResponse
    {
        $account->update([
            'mt5_account_id' => $request->validated('mt5_account_id'),
        ]);

        return redirect()->route('forex.client-accounts');
    }
}
