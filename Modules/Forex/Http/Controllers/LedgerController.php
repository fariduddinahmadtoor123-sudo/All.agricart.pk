<?php

namespace Modules\Forex\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use Modules\Forex\Http\Requests\StoreLedgerEntryRequest;
use Modules\Forex\Http\Requests\StoreWalletAmountRequest;
use Modules\Forex\Models\ClientAccount;
use Modules\Forex\Support\Ledger;
use Modules\Forex\Support\WalletBook;

class LedgerController extends Controller
{
    public function __invoke(Request $request, Ledger $ledger): View
    {
        $accounts = ClientAccount::query()
            ->with(['user.wallet', 'mt5Account'])
            ->orderBy('number')
            ->get();

        return view('forex.ledger', [
            'user' => $request->user(),
            'accounts' => $accounts,
            'figures' => $ledger->figures($accounts->modelKeys()),
        ]);
    }

    public function show(Request $request, ClientAccount $account, Ledger $ledger, WalletBook $wallets): View
    {
        $account->load(['user', 'mt5Account']);
        $wallet = $wallets->open($account->user);
        $lines = collect($ledger->lines($account))
            ->concat($wallets->externalLines($wallet))
            ->sortByDesc(fn (array $line) => $line['closed_at'] ?? $line['opened_at'])
            ->values()
            ->all();

        return view('forex.ledger.show', [
            'user' => $request->user(),
            'account' => $account,
            'wallet' => $wallet,
            'lines' => $lines,
        ]);
    }

    public function depositWallet(StoreWalletAmountRequest $request, ClientAccount $account, WalletBook $wallets): RedirectResponse
    {
        try {
            $wallets->deposit($account, $request->validated('amount'), $request->user());
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->withErrors(['amount' => $exception->getMessage()]);
        }

        return redirect()
            ->route('forex.ledger.show', $account)
            ->with('status', 'Deposited into the wallet.');
    }

    public function create(Request $request): View
    {
        return view('forex.ledger.create', [
            'user' => $request->user(),
            'accounts' => ClientAccount::query()->with('user')->orderBy('number')->get(),
        ]);
    }

    public function store(StoreLedgerEntryRequest $request, Ledger $ledger): RedirectResponse
    {
        $account = ClientAccount::query()->findOrFail($request->integer('client_account_id'));

        try {
            $ledger->post($account, $request->validated(), $request->user());
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->withErrors(['amount' => $exception->getMessage()]);
        }

        return redirect()->route('forex.ledger.show', $account);
    }
}
