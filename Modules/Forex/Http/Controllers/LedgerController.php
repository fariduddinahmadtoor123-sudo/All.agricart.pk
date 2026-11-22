<?php

namespace Modules\Forex\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use Modules\Forex\Http\Requests\StoreLedgerEntryRequest;
use Modules\Forex\Models\ClientAccount;
use Modules\Forex\Models\LedgerEntry;
use Modules\Forex\Support\Ledger;

class LedgerController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('forex.ledger', [
            'user' => $request->user(),
            'accounts' => ClientAccount::query()
                ->with(['user', 'mt5Account'])
                ->orderBy('number')
                ->get(),
            'entries' => LedgerEntry::query()
                ->with(['clientAccount.user', 'author'])
                ->latest('id')
                ->limit(100)
                ->get(),
        ]);
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

        return redirect()->route('forex.ledger');
    }
}
