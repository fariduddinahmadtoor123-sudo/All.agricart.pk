<?php

namespace Modules\Forex\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use InvalidArgumentException;
use Modules\Forex\Http\Requests\CancelPendingOrderRequest;
use Modules\Forex\Http\Requests\CloseOrderRequest;
use Modules\Forex\Http\Requests\ClosePositionsRequest;
use Modules\Forex\Http\Requests\OpenPositionsRequest;
use Modules\Forex\Http\Requests\PlacePendingOrderRequest;
use Modules\Forex\Http\Requests\StoreTerminalPaymentRequest;
use Modules\Forex\Http\Requests\UpdateOrderLevelsRequest;
use Modules\Forex\Http\Requests\UpdateWatchlistRequest;
use Modules\Forex\Models\ClientAccount;
use Modules\Forex\Models\Payment;
use Modules\Forex\Models\Position;
use Modules\Forex\Support\Ledger;
use Modules\Forex\Support\MarketWatch;
use Modules\Forex\Support\Symbols;
use Modules\Forex\Support\Trading;
use Modules\Forex\Support\WalletBook;
use Modules\Forex\Support\Watchlist;

class MarketWatchController extends Controller
{
    public function __invoke(Request $request, Ledger $ledger): View
    {
        $user = $request->user();
        $gateQuotes = MarketWatch::gateRows($user);
        $liveBySymbol = collect($gateQuotes)
            ->filter(fn (array $quote): bool => $quote['live'])
            ->pluck('symbol')
            ->all();

        return view('forex.market-watch', [
            'user' => $user,
            'quotes' => $gateQuotes,
            'orders' => MarketWatch::book($user),
            'account' => MarketWatch::account($user),
            'sessions' => MarketWatch::sessions(),
            'liquidity' => MarketWatch::liquidity(),
            'balance' => MarketWatch::balanceFor($user),
            'watchlist' => Watchlist::for($user),
            'categories' => Symbols::categories(),
            'groupedSymbols' => Symbols::groupedAllowed(),
            'liveSymbols' => $liveBySymbol,
            'feedOnline' => MarketWatch::onlineFeed($user) !== null,
            ...$this->terminalMoney($user, $ledger),
        ]);
    }

    public function ledger(Request $request, Ledger $ledger): View
    {
        return view('forex.terminal.ledger', [
            'user' => $request->user(),
            ...$this->terminalMoney($request->user(), $ledger),
        ]);
    }

    public function payments(Request $request, WalletBook $wallets): View
    {
        return view('forex.terminal.payments', [
            'user' => $request->user(),
            'clientAccount' => $request->user()->clientAccount,
            'wallet' => $wallets->open($request->user()),
        ]);
    }

    public function storePayment(StoreTerminalPaymentRequest $request, WalletBook $wallets): RedirectResponse
    {
        $account = $request->user()->clientAccount;
        $withdrawal = $request->string('type')->toString() === 'withdrawal';

        try {
            if ($withdrawal) {
                $wallets->toWallet($account, $request->validated('amount'), $request->user());
            } else {
                $wallets->toAccount($account, $request->validated('amount'), $request->user());
            }
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->withErrors(['amount' => $exception->getMessage()]);
        }

        $back = $request->input('return') === 'page'
            ? route('forex.market-watch.payments')
            : route('forex.market-watch');

        return redirect($back)->with('status', $withdrawal ? 'Moved into the wallet.' : 'Moved into the account.');
    }

    public function quotes(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($request->boolean('ticks')) {
            return response()->json([
                'quotes' => MarketWatch::gateTicks($user),
                'account' => MarketWatch::account($user),
            ]);
        }

        return response()->json([
            'quotes' => MarketWatch::gateRows($user),
            'orders' => MarketWatch::book($user),
            'account' => MarketWatch::account($user),
            'sessions' => MarketWatch::sessions(),
            'liquidity' => MarketWatch::liquidity(),
            'watchlist' => Watchlist::for($user),
        ]);
    }

    public function open(OpenPositionsRequest $request, Trading $trading): RedirectResponse|JsonResponse
    {
        try {
            $trading->open(
                $request->user(),
                $request->string('symbol')->toString(),
                $request->string('side')->toString(),
                (float) $request->input('quantity'),
            );
        } catch (InvalidArgumentException $exception) {
            return $this->tradeBack($request, $exception->getMessage());
        }

        return $this->tradeBack($request);
    }

    public function pending(PlacePendingOrderRequest $request, Trading $trading): RedirectResponse|JsonResponse
    {
        try {
            $trading->placePending(
                $request->user(),
                $request->string('symbol')->toString(),
                $request->string('order_type')->toString(),
                (float) $request->input('quantity'),
                (float) $request->input('trigger_price'),
                [
                    'stop_loss' => $request->input('stop_loss'),
                    'take_profit' => $request->input('take_profit'),
                ],
            );
        } catch (InvalidArgumentException $exception) {
            return $this->tradeBack($request, $exception->getMessage());
        }

        return $this->tradeBack($request);
    }

    public function levels(UpdateOrderLevelsRequest $request, Position $position, Trading $trading): RedirectResponse|JsonResponse
    {
        try {
            $trading->updateLevels(
                $request->user(),
                $position,
                $request->filled('stop_loss') ? (float) $request->input('stop_loss') : null,
                $request->filled('take_profit') ? (float) $request->input('take_profit') : null,
            );
        } catch (InvalidArgumentException $exception) {
            return $this->tradeBack($request, $exception->getMessage());
        }

        return $this->tradeBack($request);
    }

    public function cancel(CancelPendingOrderRequest $request, Position $position, Trading $trading): RedirectResponse|JsonResponse
    {
        try {
            $trading->cancelPending($request->user(), $position);
        } catch (InvalidArgumentException $exception) {
            return $this->tradeBack($request, $exception->getMessage());
        }

        return $this->tradeBack($request);
    }

    public function close(ClosePositionsRequest $request, Trading $trading): RedirectResponse|JsonResponse
    {
        try {
            $trading->close(
                $request->user(),
                $request->string('symbol')->toString(),
                $request->string('side')->toString(),
            );
        } catch (InvalidArgumentException $exception) {
            return $this->tradeBack($request, $exception->getMessage());
        }

        return $this->tradeBack($request);
    }

    public function closeOrder(CloseOrderRequest $request, Position $position, Trading $trading): RedirectResponse|JsonResponse
    {
        try {
            $trading->closeOrder($request->user(), $position);
        } catch (InvalidArgumentException $exception) {
            return $this->tradeBack($request, $exception->getMessage());
        }

        return $this->tradeBack($request);
    }

    public function watchlist(UpdateWatchlistRequest $request): RedirectResponse
    {
        $symbols = $request->input('symbols', []);

        if (! is_array($symbols)) {
            $symbols = [];
        }

        $custom = strtoupper(trim((string) $request->input('custom', '')));

        if ($custom !== '') {
            if (! Symbols::allows($custom)) {
                return redirect()
                    ->route('forex.market-watch')
                    ->withErrors([
                        'custom' => 'This symbol is closed by the broker. You cannot get rates on this symbol.',
                    ])
                    ->withInput();
            }

            $symbols[] = $custom;
        }

        Watchlist::sync($request->user(), $symbols);

        $mt5Id = $request->user()->clientAccount?->mt5_account_id;

        if ($mt5Id) {
            Cache::forget('forex.connector.watch.'.$mt5Id);
        }

        return redirect()->route('forex.market-watch');
    }

    /**
     * @return array{clientAccount: ?ClientAccount, ledgerLines: list<array<string, mixed>>, paymentRequests: Collection<int, Payment>, profit: array{profit: string, tone: string}, openedAt: CarbonInterface}
     */
    private function terminalMoney(User $user, Ledger $ledger): array
    {
        $account = $user->clientAccount;
        $figures = $account ? $ledger->figures([$account->id]) : [];

        return [
            'clientAccount' => $account,
            'wallet' => app(WalletBook::class)->open($user),
            'ledgerLines' => $account ? $ledger->moneyLines($account) : [],
            'paymentRequests' => $account
                ? Payment::query()
                    ->where('client_account_id', $account->id)
                    ->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_REJECTED])
                    ->latest('id')
                    ->get()
                : collect(),
            'profit' => $account ? ($figures[$account->id] ?? ['profit' => '0.00', 'tone' => '']) : ['profit' => '0.00', 'tone' => ''],
            'openedAt' => now(),
        ];
    }

    private function tradeBack(Request $request, ?string $error = null): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            if ($error !== null) {
                return response()->json(['message' => $error], 422);
            }

            return response()->json(['ok' => true]);
        }

        if ($error !== null) {
            return redirect()
                ->route('forex.market-watch')
                ->withErrors(['trade' => $error]);
        }

        return redirect()->route('forex.market-watch');
    }
}
