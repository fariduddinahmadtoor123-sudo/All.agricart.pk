# Desk MT5 connector

This is the connector that keeps Desk rates on the same tick as MetaTrader 5. Rebuild it this way. Do not go back to painting the screen from Laravel.

## Why this one

The black connector window reads ticks from MT5 on this PC and keeps them in memory. It also listens on:

`http://127.0.0.1:8766`

Market Watch and Trade Terminal ask that address for `GET /quotes` about every 20 milliseconds. A read is a few milliseconds. The numbers on the screen are the terminal's bid, ask, high, low, and time.

Laravel is only the slow copy. The connector posts the same ticks to `POST /api/forex/connector/sync` about every 0.5 seconds so Desk can remember that the account is online. The screen must not wait for that save.

## Pieces

| Piece | Path |
| --- | --- |
| Connector | `Modules/Forex/bridge/mt5_bridge.py` |
| Start script writer | `Modules/Forex/Support/ConnectorPackage.php` |
| Market Watch paint | `resources/views/forex/watch.blade.php` |
| Trade Terminal paint | `resources/views/forex/market-watch.blade.php` |
| Saved copy | `POST /api/forex/connector/sync` in `Modules/Forex/Http/Controllers/ConnectorSyncController.php` |

Desk copies the connector into `storage/app/connectors/ready/{id}-{name}/` when Start connector is used. That folder is generated. Do not hand-edit it and do not commit it.

## Listen address

- Host `127.0.0.1`
- Port `8766`
- Path `GET /quotes`
- CORS allows the Desk page on `http://desk.test` to read it, including a private-network preflight

The window prints:

`Desk connector listening on http://127.0.0.1:8766`

Leave that window open. Closing it stops the live rates.

`/quotes` returns JSON:

```json
{
  "ok": true,
  "quotes": [
    {
      "symbol": "EURUSD",
      "bid": "1.09668",
      "ask": "1.09688",
      "spread": "2.0",
      "high": "1.09688",
      "low": "1.09668",
      "range": "0.00020",
      "time": "21:58:59",
      "live": true,
      "broker_open": true
    }
  ]
}
```

`time` is UTC, the same clock as Desk (`app.timezone` is UTC).

If port 8766 is already taken, the window says it cannot listen. The pages then fall back to Laravel and the rates feel slow again. Free the port and start the connector from Desk.

## Start it

1. The MT5 account must exist in Desk and be On.
2. MetaTrader 5 on this PC must be open and signed in to that same login.
3. Use **Start connector** on the MT5 accounts page. That rewrites `start-mt5.bat` with the current account id and connector token, then opens the window.

The token is a Bearer token on the Laravel sync only. The local `/quotes` feed does not use it. The screen and the terminal are on the same PC.

## After the accounts are wiped

An old window or an old folder such as `1-Demo-standard` still sends the deleted account id and the old token. Desk answers `401 Unauthorized`. Close that window, do not run the old bat, and start the connector again from the account that exists now.

## Timers

| Timer | Value | Job |
| --- | --- | --- |
| `MT5_QUOTE_SECONDS` | 0.01 | Read MT5 ticks into memory |
| Screen poll | 20 ms | Paint Market Watch and Trade Terminal from `127.0.0.1:8766` |
| `MT5_PUSH_SECONDS` | 0.5 | Save a copy into Laravel |

Set these in `ConnectorPackage` when it writes `start-mt5.bat`, and keep the same host and port in both Blade pages.
