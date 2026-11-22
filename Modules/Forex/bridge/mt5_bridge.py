"""Desk MT5 connector.

Reads live ticks from MetaTrader 5 on this PC and posts them to Desk.
Keep this window open while trading rates should stay live.

Environment (filled by start-mt5.bat from Desk download):
  LARAVEL_API_URL
  DESK_CONNECTOR_TOKEN
  DESK_MT5_ACCOUNT_ID
  MT5_LOGIN
  MT5_SERVER
  MT5_PASSWORD          optional
  MT5_TERMINAL_PATH     optional
  MT5_SYMBOLS           default 15 desk symbols (see Desk config/forex.php)
  MT5_QUOTE_SECONDS     default 0.01
  MT5_PUSH_SECONDS      default 0.5
  DESK_BRIDGE_HOST      default 127.0.0.1
  DESK_BRIDGE_PORT      default 8766
"""

from __future__ import annotations

import json
import os
import queue
import subprocess
import threading
import time
import urllib.error
import urllib.request
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer

try:
    import MetaTrader5 as mt5
except ImportError:  # pragma: no cover
    mt5 = None

LARAVEL = os.environ.get("LARAVEL_API_URL", "").strip().rstrip("/")
TOKEN = os.environ.get("DESK_CONNECTOR_TOKEN", "").strip()
ACCOUNT_ID = os.environ.get("DESK_MT5_ACCOUNT_ID", "").strip()
QUOTE_SECONDS = max(0.005, float(os.environ.get("MT5_QUOTE_SECONDS", "0.01")))
PUSH_SECONDS = max(0.2, float(os.environ.get("MT5_PUSH_SECONDS", "0.5")))
BRIDGE_HOST = os.environ.get("DESK_BRIDGE_HOST", "127.0.0.1").strip() or "127.0.0.1"
BRIDGE_PORT = int(os.environ.get("DESK_BRIDGE_PORT", "8766"))
SYMBOLS = [
    part.strip()
    for part in os.environ.get(
        "MT5_SYMBOLS",
        "EURUSD,GBPUSD,USDJPY,USDCHF,AUDUSD,NZDUSD,USDCAD,EURGBP,XAUUSD,XAGUSD,XTIUSD,BTCUSD,ETHUSD,US30,NAS100",
    ).split(",")
    if part.strip()
][:15]

QUOTES: dict[str, dict] = {}
QUOTES_LOCK = threading.Lock()
DIGITS: dict[str, int] = {}
RANGES: dict[str, tuple[float, float]] = {}
PUBLISH: queue.Queue = queue.Queue(1)
LAST_ATTACHED = 0.0
RESOLVED: list[str] = []
RESOLVED_MAP: dict[str, str] = {}
RESOLVED_FOR: tuple[str, ...] = ()


def terminal_process_running() -> bool:
    path = (os.environ.get("MT5_TERMINAL_PATH") or "").strip()
    image = os.path.basename(path) or "terminal64.exe"

    try:
        completed = subprocess.run(
            ["tasklist", "/FI", f"IMAGENAME eq {image}", "/NH"],
            capture_output=True,
            text=True,
            timeout=5,
            check=False,
            creationflags=0x08000000,
        )
    except (OSError, subprocess.SubprocessError, ValueError):
        return False

    listing = (completed.stdout or "").lower()

    return image.lower() in listing and "no tasks are running" not in listing


def expected_login() -> str:
    return os.environ.get("MT5_LOGIN", "").strip()


def login_matches(info) -> bool:
    login = expected_login()
    if login == "" or info is None:
        return info is not None

    return str(info.login) == str(login)


def ensure_terminal() -> bool:
    global LAST_ATTACHED

    if mt5 is None:
        return False

    now = time.time()
    login = expected_login()
    password = os.environ.get("MT5_PASSWORD", "")
    server = os.environ.get("MT5_SERVER", "")

    try:
        info = mt5.account_info()
        connected = mt5.terminal_info() is not None and info is not None
    except Exception:
        connected = False
        info = None

    if connected and login_matches(info):
        LAST_ATTACHED = now
        return True

    if LAST_ATTACHED and now - LAST_ATTACHED < 1 and connected:
        return False

    if not terminal_process_running():
        return False

    path = (os.environ.get("MT5_TERMINAL_PATH") or "").strip()
    attached = mt5.initialize(path) if path else mt5.initialize()
    if not attached:
        return False

    if login:
        if not mt5.login(int(login), password=password, server=server):
            return False
    elif mt5.account_info() is None:
        return False

    info = mt5.account_info()
    if not login_matches(info):
        if info is not None:
            print(f"Desk connector: open terminal login {info.login} does not match {login}", flush=True)
        return False

    LAST_ATTACHED = time.time()
    return True


def resolve_symbol(name: str) -> str | None:
    aliases = [name]
    upper = name.upper()
    if upper == "BTCUSD":
        aliases.extend(["BTCUSDT", "BTCUSD.m", "BTCUSDm"])
    if upper == "ETHUSD":
        aliases.extend(["ETHUSDT", "ETHUSD.m", "ETHUSDm"])
    if upper == "XAUUSD":
        aliases.extend(["GOLD", "XAUUSDm"])
    if upper == "XAGUSD":
        aliases.extend(["SILVER", "XAGUSDm"])
    if upper == "XTIUSD":
        aliases.extend(["USOIL", "WTI", "WTICO", "XTIUSD.m", "USOILm"])
    if upper == "US30":
        aliases.extend(["US30.cash", "DJ30", "US30m"])
    if upper == "NAS100":
        aliases.extend(["USTEC", "NAS100.cash", "NAS100m", "NDX"])

    candidates: list[str] = []
    for alias in aliases:
        for suffix in ("", "m", ".", ".m", "pro", ".pro"):
            candidates.append(f"{alias}{suffix}")

    for candidate in candidates:
        if not mt5.symbol_select(candidate, True):
            continue
        tick = mt5.symbol_info_tick(candidate)
        if tick is not None and tick.bid:
            return candidate

    wanted = {alias.upper() for alias in aliases}
    discovered = mt5.symbols_get()
    if discovered is None:
        return None

    for symbol in discovered:
        key = symbol.name.upper()
        if key not in wanted and not any(key.startswith(alias) for alias in wanted):
            continue
        if not mt5.symbol_select(symbol.name, True):
            continue
        tick = mt5.symbol_info_tick(symbol.name)
        if tick is not None and tick.bid:
            return symbol.name

    return None


def apply_watch_symbols(names: list[str] | None) -> None:
    global SYMBOLS, RESOLVED, RESOLVED_MAP, RESOLVED_FOR

    if not names:
        return

    cleaned = [str(name).strip().upper() for name in names if str(name).strip()][:15]
    if not cleaned:
        return

    key = tuple(cleaned)
    if key == tuple(SYMBOLS):
        return

    SYMBOLS = cleaned
    RESOLVED = []
    RESOLVED_MAP = {}
    RESOLVED_FOR = ()
    print(f"Desk connector: watch list updated ({len(SYMBOLS)} symbols)", flush=True)


def watched_symbols() -> list[str]:
    global RESOLVED, RESOLVED_MAP, RESOLVED_FOR

    wanted = tuple(SYMBOLS)
    if RESOLVED and RESOLVED_FOR == wanted:
        return RESOLVED

    names: list[str] = []
    mapping: dict[str, str] = {}
    for name in SYMBOLS:
        resolved = resolve_symbol(name)
        if not resolved:
            continue
        if resolved not in names:
            names.append(resolved)
        mapping[resolved] = name.upper()

    RESOLVED = names
    RESOLVED_MAP = mapping
    RESOLVED_FOR = wanted
    print(f"Desk connector: watching {len(names)} symbols: {', '.join(mapping.values()) or 'none'}", flush=True)
    return names


def digits_for(symbol: str) -> int:
    cached = DIGITS.get(symbol)
    if cached:
        return cached

    info = mt5.symbol_info(symbol)
    if info is not None and info.digits:
        DIGITS[symbol] = int(info.digits)
        return DIGITS[symbol]

    key = symbol.upper()
    if key.startswith(("XAU", "XAG")):
        digits = 2
    elif key.startswith(("US30", "BTC")):
        digits = 2
    else:
        digits = 5

    DIGITS[symbol] = digits
    return digits


def refresh_quotes() -> None:
    now_ms = int(time.time() * 1000)
    rows: dict[str, dict] = {}

    for symbol in watched_symbols():
        tick = mt5.symbol_info_tick(symbol)
        if tick is None or not tick.bid:
            continue

        desk = RESOLVED_MAP.get(symbol, symbol.upper())
        digits = digits_for(symbol)
        high, low = RANGES.get(desk, (tick.bid, tick.bid))
        high = max(high, tick.bid, tick.ask)
        low = min(low, tick.bid, tick.ask)
        RANGES[desk] = (high, low)

        rows[desk] = {
            "symbol": desk,
            "bid": round(float(tick.bid), digits),
            "ask": round(float(tick.ask), digits),
            "high": round(float(high), digits),
            "low": round(float(low), digits),
            "spread": round(float(tick.ask - tick.bid), digits),
            "digits": digits,
            "time_msc": int(tick.time_msc),
            "received_ms": now_ms,
        }

    if not rows:
        return

    with QUOTES_LOCK:
        QUOTES.clear()
        QUOTES.update(rows)


def account_payload() -> dict | None:
    info = mt5.account_info()
    if info is None:
        return None

    return {
        "login": str(info.login),
        "balance": float(info.balance),
        "equity": float(info.equity),
        "margin": float(getattr(info, "margin", 0) or 0),
        "free_margin": float(getattr(info, "margin_free", 0) or 0),
        "profit": float(getattr(info, "profit", 0) or 0),
        "server": getattr(info, "server", "") or "",
        "company": getattr(info, "company", "") or "",
        "name": getattr(info, "name", "") or "",
    }


def enqueue_snapshot() -> None:
    with QUOTES_LOCK:
        ticks = list(QUOTES.values())

    payload = {
        "mt5_account_id": ACCOUNT_ID,
        "account": account_payload(),
        "ticks": ticks,
    }

    try:
        PUBLISH.put_nowait(payload)
        return
    except queue.Full:
        pass

    try:
        PUBLISH.get_nowait()
    except queue.Empty:
        pass

    try:
        PUBLISH.put_nowait(payload)
    except queue.Full:
        pass


def laravel_post(path: str, payload: dict) -> dict:
    if LARAVEL == "" or TOKEN == "":
        raise RuntimeError("LARAVEL_API_URL or DESK_CONNECTOR_TOKEN is missing.")

    request = urllib.request.Request(
        LARAVEL + path,
        data=json.dumps(payload).encode(),
        headers={
            "Authorization": f"Bearer {TOKEN}",
            "Accept": "application/json",
            "Content-Type": "application/json",
        },
        method="POST",
    )

    with urllib.request.urlopen(request, timeout=8) as response:
        body = response.read().decode("utf-8", errors="replace")

    if not body:
        return {}

    try:
        data = json.loads(body)
    except json.JSONDecodeError:
        return {}

    return data if isinstance(data, dict) else {}


def publish_loop() -> None:
    while True:
        payload = PUBLISH.get()
        try:
            reply = laravel_post("/api/forex/connector/sync", payload)
            apply_watch_symbols(reply.get("watch_symbols") if isinstance(reply, dict) else None)
            print("Desk connector: rates sent", flush=True)
        except Exception as error:
            print(f"Desk connector publish failed: {error}", flush=True)


def bridge_loop() -> None:
    next_quotes = 0.0
    next_push = 0.0

    while True:
        if not ensure_terminal():
            time.sleep(0.5)
            continue

        now = time.time()
        if now >= next_quotes:
            refresh_quotes()
            next_quotes = time.time() + QUOTE_SECONDS

        now = time.time()
        if now >= next_push:
            enqueue_snapshot()
            next_push = time.time() + PUSH_SECONDS

        time.sleep(0.001)


def price_text(value: float, digits: int) -> str:
    return f"{value:.{digits}f}"


def spread_text(spread: float, symbol: str, digits: int) -> str:
    key = symbol.upper()
    if key.startswith(("XAU", "XAG", "US30", "NAS", "BTC")):
        return f"{spread:.{max(2, digits)}f}".rstrip("0").rstrip(".") or "0"

    points = spread * (100 if "JPY" in key else 10000)
    return f"{points:.1f}"


def quote_board() -> dict:
    with QUOTES_LOCK:
        ticks = list(QUOTES.values())

    quotes = []
    for tick in ticks:
        digits = int(tick.get("digits") or 5)
        bid = float(tick["bid"])
        ask = float(tick["ask"])
        high = float(tick["high"])
        low = float(tick["low"])
        stamp = int(tick.get("time_msc") or 0) / 1000
        quotes.append({
            "symbol": tick["symbol"],
            "bid": price_text(bid, digits),
            "ask": price_text(ask, digits),
            "spread": spread_text(max(0.0, ask - bid), str(tick["symbol"]), digits),
            "high": price_text(high, digits),
            "low": price_text(low, digits),
            "range": price_text(max(0.0, high - low), digits),
            "time": time.strftime("%H:%M:%S", time.gmtime(stamp or time.time())),
            "live": True,
            "broker_open": True,
        })

    return {"ok": True, "quotes": quotes}


class QuoteHandler(BaseHTTPRequestHandler):
    def do_OPTIONS(self) -> None:  # noqa: N802
        self.respond(204, {})

    def do_GET(self) -> None:  # noqa: N802
        path = self.path.split("?", 1)[0]
        if path == "/quotes":
            self.respond(200, quote_board())
            return

        self.respond(404, {"ok": False, "message": "Not found."})

    def respond(self, status: int, payload: dict) -> None:
        body = json.dumps(payload).encode()
        self.send_response(status)
        self.send_header("Content-Type", "application/json")
        self.send_header("Cache-Control", "no-store")
        self.send_header("Access-Control-Allow-Origin", "*")
        self.send_header("Access-Control-Allow-Private-Network", "true")
        self.send_header("Access-Control-Allow-Methods", "GET, OPTIONS")
        self.send_header("Access-Control-Allow-Headers", "Accept, Content-Type")
        self.send_header("Content-Length", str(len(body)))
        self.end_headers()
        self.wfile.write(body)

    def log_message(self, format: str, *args) -> None:  # noqa: A003
        return


def serve_quotes() -> None:
    try:
        server = ThreadingHTTPServer((BRIDGE_HOST, BRIDGE_PORT), QuoteHandler)
    except OSError as error:
        print(f"Desk connector: cannot listen on {BRIDGE_HOST}:{BRIDGE_PORT}: {error}", flush=True)
        return

    print(f"Desk connector listening on http://{BRIDGE_HOST}:{BRIDGE_PORT}", flush=True)
    server.serve_forever()


def main() -> None:
    print("Desk MT5 connector", flush=True)
    print(f"Desk API: {LARAVEL or '(missing)'}", flush=True)
    print(f"Account id: {ACCOUNT_ID or '(missing)'}", flush=True)
    print(f"Login: {os.environ.get('MT5_LOGIN', '') or '(open terminal)'}", flush=True)
    print("Leave this window open. Closing it stops live rates.", flush=True)

    if mt5 is None:
        raise SystemExit("MetaTrader5 Python package is not installed.")

    threading.Thread(target=serve_quotes, name="desk-quotes", daemon=True).start()
    threading.Thread(target=publish_loop, name="desk-publish", daemon=True).start()
    bridge_loop()


if __name__ == "__main__":
    main()
