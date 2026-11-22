<?php

namespace Modules\Forex\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use InvalidArgumentException;
use Modules\Forex\Http\Requests\CancelPendingOrderRequest;
use Modules\Forex\Http\Requests\CloseOrderRequest;
use Modules\Forex\Http\Requests\ClosePositionsRequest;
use Modules\Forex\Http\Requests\OpenPositionsRequest;
use Modules\Forex\Http\Requests\PlacePendingOrderRequest;
use Modules\Forex\Http\Requests\UpdateOrderLevelsRequest;
use Modules\Forex\Http\Requests\UpdateWatchlistRequest;
use Modules\Forex\Models\Position;
use Modules\Forex\Support\MarketWatch;
use Modules\Forex\Support\Symbols;
use Modules\Forex\Support\Trading;
use Modules\Forex\Support\Watchlist;

class MarketWatchController extends Controller
{
    public function __invoke(Request $request): View
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
        ]);
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

    public function open(OpenPositionsRequest $request, Trading $trading): RedirectResponse
    {
        try {
            $trading->open(
                $request->user(),
                $request->string('symbol')->toString(),
                $request->string('side')->toString(),
                (float) $request->input('quantity'),
            );
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route('forex.market-watch')
                ->withErrors(['trade' => $exception->getMessage()]);
        }

        return redirect()->route('forex.market-watch');
    }

    public function pending(PlacePendingOrderRequest $request, Trading $trading): RedirectResponse
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
            return redirect()
                ->route('forex.market-watch')
                ->withErrors(['trade' => $exception->getMessage()]);
        }

        return redirect()->route('forex.market-watch');
    }

    public function levels(UpdateOrderLevelsRequest $request, Position $position, Trading $trading): RedirectResponse
    {
        try {
            $trading->updateLevels(
                $request->user(),
                $position,
                $request->filled('stop_loss') ? (float) $request->input('stop_loss') : null,
                $request->filled('take_profit') ? (float) $request->input('take_profit') : null,
            );
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route('forex.market-watch')
                ->withErrors(['trade' => $exception->getMessage()]);
        }

        return redirect()->route('forex.market-watch');
    }

    public function cancel(CancelPendingOrderRequest $request, Position $position, Trading $trading): RedirectResponse
    {
        try {
            $trading->cancelPending($request->user(), $position);
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route('forex.market-watch')
                ->withErrors(['trade' => $exception->getMessage()]);
        }

        return redirect()->route('forex.market-watch');
    }

    public function close(ClosePositionsRequest $request, Trading $trading): RedirectResponse
    {
        $trading->close(
            $request->user(),
            $request->string('symbol')->toString(),
            $request->string('side')->toString(),
        );

        return redirect()->route('forex.market-watch');
    }

    public function closeOrder(CloseOrderRequest $request, Position $position, Trading $trading): RedirectResponse
    {
        try {
            $trading->closeOrder($request->user(), $position);
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route('forex.market-watch')
                ->withErrors(['trade' => $exception->getMessage()]);
        }

        return redirect()->route('forex.market-watch');
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
}
