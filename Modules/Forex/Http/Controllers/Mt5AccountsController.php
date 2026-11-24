<?php

namespace Modules\Forex\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Forex\Http\Requests\DeactivateMt5AccountRequest;
use Modules\Forex\Http\Requests\StoreMt5AccountRequest;
use Modules\Forex\Http\Requests\UpdateMt5RatesRequest;
use Modules\Forex\Models\Mt5Account;
use Modules\Forex\Models\Position;
use Modules\Forex\Support\ConnectorPackage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class Mt5AccountsController extends Controller
{
    public function __invoke(Request $request): View
    {
        $accounts = Mt5Account::query()->latest('id')->get();

        $cuts = Position::query()
            ->join('forex_client_accounts as clients', 'clients.id', '=', 'forex_positions.client_account_id')
            ->where('forex_positions.status', Position::STATUS_CLOSED)
            ->whereNotNull('clients.mt5_account_id')
            ->groupBy('clients.mt5_account_id')
            ->selectRaw('clients.mt5_account_id as mt5_account_id, coalesce(sum(forex_positions.commission), 0) as customer_cut, coalesce(sum(forex_positions.partner_commission), 0) as partner_cut')
            ->get()
            ->keyBy('mt5_account_id');

        return view('forex.mt5-accounts', [
            'user' => $request->user(),
            'accounts' => $accounts,
            'cuts' => $cuts,
        ]);
    }

    public function create(Request $request): View
    {
        return view('forex.mt5-accounts.create', [
            'user' => $request->user(),
        ]);
    }

    public function store(StoreMt5AccountRequest $request): RedirectResponse
    {
        Mt5Account::query()->create([
            ...$request->validated(),
            'active' => true,
        ]);

        return redirect()->route('forex.mt5-accounts');
    }

    public function rates(UpdateMt5RatesRequest $request, Mt5Account $account): RedirectResponse
    {
        $account->forceFill([
            'partner_rate' => $request->validated('partner_rate'),
            'customer_rate' => $request->validated('customer_rate'),
        ])->save();

        return redirect()->route('forex.mt5-accounts');
    }

    public function deactivate(DeactivateMt5AccountRequest $request, Mt5Account $account): RedirectResponse
    {
        $account->forceFill([
            'active' => false,
            'deactivated_reason' => $request->validated('reason'),
            'deactivated_at' => now(),
        ])->save();

        return redirect()->route('forex.mt5-accounts');
    }

    public function reactivate(Mt5Account $account): RedirectResponse
    {
        $account->forceFill([
            'active' => true,
            'deactivated_reason' => null,
            'deactivated_at' => null,
        ])->save();

        return redirect()->route('forex.mt5-accounts');
    }

    public function connector(Mt5Account $account, ConnectorPackage $package): BinaryFileResponse
    {
        $path = $package->build($account);

        return response()
            ->download($path, $package->downloadName($account))
            ->deleteFileAfterSend();
    }

    public function prepareConnector(Mt5Account $account, ConnectorPackage $package): RedirectResponse
    {
        $directory = $package->prepareLocal($account);

        return redirect()
            ->route('forex.mt5-accounts')
            ->with('connector_ready', $directory.DIRECTORY_SEPARATOR.'start-mt5.bat');
    }

    public function startConnector(Mt5Account $account, ConnectorPackage $package): RedirectResponse
    {
        try {
            $bat = $package->startLocal($account);
        } catch (\Throwable $exception) {
            return redirect()
                ->route('forex.mt5-accounts')
                ->withErrors(['connector' => $exception->getMessage()]);
        }

        return redirect()
            ->route('forex.mt5-accounts')
            ->with('connector_started', $bat);
    }
}
