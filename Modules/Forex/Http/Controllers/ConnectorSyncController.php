<?php

namespace Modules\Forex\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Modules\Forex\Models\Mt5Account;
use Modules\Forex\Models\Mt5Quote;
use Modules\Forex\Support\Symbols;
use Modules\Forex\Support\Watchlist;

class ConnectorSyncController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $token = $this->bearerToken($request);

        if ($token === null) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $account = Mt5Account::query()->where('connector_token', $token)->first();

        if ($account === null || ! $account->active) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $payloadAccountId = (string) $request->input('mt5_account_id', '');

        if ($payloadAccountId === '' || $payloadAccountId !== (string) $account->id) {
            return response()->json(['message' => 'Account mismatch.'], 422);
        }

        $payloadLogin = data_get($request->input('account'), 'login');

        if (is_scalar($payloadLogin) && (string) $payloadLogin !== '' && (string) $payloadLogin !== (string) $account->login) {
            return response()->json(['message' => 'Login mismatch.'], 422);
        }

        $ticks = $request->input('ticks', []);

        if (! is_array($ticks)) {
            return response()->json(['message' => 'Ticks must be a list.'], 422);
        }

        if (count($ticks) > Symbols::max()) {
            return response()->json(['message' => 'Too many ticks.'], 422);
        }

        $allowed = array_flip(Symbols::allowed());
        $rows = [];
        $now = now();

        foreach ($ticks as $tick) {
            if (! is_array($tick) || blank($tick['symbol'] ?? null)) {
                continue;
            }

            $symbol = strtoupper((string) $tick['symbol']);

            if (! isset($allowed[$symbol]) || strlen($symbol) > 32) {
                continue;
            }

            $bid = (float) ($tick['bid'] ?? 0);
            $ask = (float) ($tick['ask'] ?? 0);

            if (! is_finite($bid) || ! is_finite($ask) || $bid <= 0 || $ask <= 0) {
                continue;
            }

            $tickAt = $now;

            if (isset($tick['time_msc']) && is_numeric($tick['time_msc'])) {
                try {
                    $tickAt = Carbon::createFromTimestampMs((int) $tick['time_msc']);
                } catch (\Throwable) {
                    $tickAt = $now;
                }
            }

            $digits = isset($tick['digits']) && is_numeric($tick['digits'])
                ? max(0, min(8, (int) $tick['digits']))
                : null;

            $rows[$symbol] = [
                'mt5_account_id' => $account->id,
                'symbol' => $symbol,
                'bid' => $bid,
                'ask' => $ask,
                'high' => isset($tick['high']) ? (float) $tick['high'] : null,
                'low' => isset($tick['low']) ? (float) $tick['low'] : null,
                'spread' => isset($tick['spread']) ? (float) $tick['spread'] : round($ask - $bid, 8),
                'digits' => $digits,
                'tick_at' => $tickAt,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $snapshot = $this->accountSnapshot($request->input('account'));

        if ($rows === [] && $snapshot === []) {
            return response()->json(['message' => 'No valid ticks.'], 422);
        }

        if ($rows !== []) {
            Mt5Quote::query()->upsert(
                array_values($rows),
                ['mt5_account_id', 'symbol'],
                ['bid', 'ask', 'high', 'low', 'spread', 'digits', 'tick_at', 'updated_at'],
            );
        }

        $account->forceFill([
            'last_seen_at' => $now,
            ...$snapshot,
        ])->save();

        return response()->json([
            'ok' => true,
            'online' => true,
            'quotes' => count($rows),
            'watch_symbols' => Cache::remember(
                'forex.connector.watch.'.$account->id,
                2,
                fn (): array => Watchlist::connectorTargets($account),
            ),
        ]);
    }

    /**
     * @return array{mt5_balance?: float, mt5_equity?: float, mt5_margin?: float, mt5_free_margin?: float, mt5_profit?: float}
     */
    private function accountSnapshot(mixed $payload): array
    {
        if (! is_array($payload)) {
            return [];
        }

        $map = [
            'balance' => 'mt5_balance',
            'equity' => 'mt5_equity',
            'margin' => 'mt5_margin',
            'free_margin' => 'mt5_free_margin',
            'profit' => 'mt5_profit',
        ];

        $out = [];

        foreach ($map as $from => $to) {
            if (! isset($payload[$from]) || ! is_numeric($payload[$from])) {
                continue;
            }

            $out[$to] = round((float) $payload[$from], 2);
        }

        return $out;
    }

    private function bearerToken(Request $request): ?string
    {
        $header = $request->header('Authorization', '');

        if (! str_starts_with($header, 'Bearer ')) {
            return null;
        }

        $token = trim(substr($header, 7));

        return $token !== '' ? $token : null;
    }
}
