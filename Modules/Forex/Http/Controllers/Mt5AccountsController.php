<?php

namespace Modules\Forex\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Forex\Http\Requests\StoreMt5AccountRequest;
use Modules\Forex\Models\Mt5Account;
use Modules\Forex\Support\ConnectorPackage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class Mt5AccountsController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('forex.mt5-accounts', [
            'user' => $request->user(),
            'accounts' => Mt5Account::query()->latest('id')->get(),
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

    public function destroy(Mt5Account $account): RedirectResponse
    {
        $account->delete();

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
