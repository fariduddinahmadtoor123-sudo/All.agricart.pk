<?php

namespace Modules\PersonalVault\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use Modules\Forex\Http\Requests\StoreWalletAmountRequest;
use Modules\Forex\Models\Payment;
use Modules\Forex\Models\WalletEntry;
use Modules\Forex\Support\Payments;
use Modules\Forex\Support\WalletBook;

class PersonalVaultController extends Controller
{
    public function show(Request $request, WalletBook $wallets): View
    {
        $user = $request->user();
        $wallet = $wallets->open($user);
        $account = $user->clientAccount;

        return view('personal-vault', [
            'user' => $user,
            'wallet' => $wallet,
            'clientAccount' => $account,
            'entries' => WalletEntry::query()
                ->where('wallet_id', $wallet->id)
                ->latest('id')
                ->get(),
            'requests' => $account
                ? Payment::query()
                    ->where('client_account_id', $account->id)
                    ->where('book', Payment::BOOK_WALLET)
                    ->where('status', Payment::STATUS_PENDING)
                    ->latest('id')
                    ->get()
                : collect(),
        ]);
    }

    public function toWallet(StoreWalletAmountRequest $request, WalletBook $wallets): RedirectResponse
    {
        return $this->move($request, function () use ($request, $wallets): void {
            $wallets->toWallet($request->user()->clientAccount, $request->validated('amount'), $request->user());
        }, 'Moved into the wallet.');
    }

    public function toAccount(StoreWalletAmountRequest $request, WalletBook $wallets): RedirectResponse
    {
        return $this->move($request, function () use ($request, $wallets): void {
            $wallets->toAccount($request->user()->clientAccount, $request->validated('amount'), $request->user());
        }, 'Moved into the account.');
    }

    public function withdraw(StoreWalletAmountRequest $request, Payments $payments, WalletBook $wallets): RedirectResponse
    {
        $account = $request->user()->clientAccount;

        if ($account === null) {
            return back()->withErrors(['amount' => 'This login has no client account yet.']);
        }

        $wallet = $wallets->open($request->user());

        if ((float) $wallet->balance < round((float) $request->validated('amount'), 2)) {
            return back()
                ->withInput()
                ->withErrors(['amount' => 'Not enough in the wallet.']);
        }

        try {
            $payments->record($account, [
                'type' => Payment::TYPE_WITHDRAWAL,
                'book' => Payment::BOOK_WALLET,
                'amount' => $request->validated('amount'),
            ], $request->user());
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->withErrors(['amount' => $exception->getMessage()]);
        }

        return redirect()
            ->route('personal-vault')
            ->with('status', 'Withdrawal request saved. It stays pending until it is approved.');
    }

    private function move(StoreWalletAmountRequest $request, callable $move, string $status): RedirectResponse
    {
        if ($request->user()->clientAccount === null) {
            return back()->withErrors(['amount' => 'This login has no client account yet.']);
        }

        try {
            $move();
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->withErrors(['amount' => $exception->getMessage()]);
        }

        return redirect()
            ->route('personal-vault')
            ->with('status', $status);
    }
}
