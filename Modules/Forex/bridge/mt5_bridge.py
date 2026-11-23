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
  MT5_PUSH_SECONDS      default 0.1
  DESK_BRIDGE_HOST      default 127.0.0.1
  DESK_BRIDGE_PORT      default 8766
"""

from __future__ import annotations

import json
import os
import queue
import re
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
PUSH_SECONDS = max(0.05, float(os.environ.get("MT5_PUSH_SECONDS", "0.1")))
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
LAST_TRY = 0.0
LAST_NOTICE = 0.0
TERMINAL_PATHS: list[str] = []
TERMINAL_PATHS_AT = 0.0
SYMBOL_CACHE: list | None = None
SYMBOL_CACHE_AT = 0.0
RESOLVED: list[str] = []
RESOLVED_MAP: dict[str, str] = {}
RESOLVED_FOR: tuple[str, ...] = ()
LAST_RESOLVE = 0.0
MAGIC = 260011
INFLIGHT: set[str] = set()
COMMAND_LOCK = threading.Lock()
FILLING: dict[str, int] = {}


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


def running_terminal_paths() -> list[str]:
    global TERMINAL_PATHS, TERMINAL_PATHS_AT

    now = time.time()
    if TERMINAL_PATHS and now - TERMINAL_PATHS_AT < 5:
        return list(TERMINAL_PATHS)

    explicit = (os.environ.get("MT5_TERMINAL_PATH") or "").strip()
    paths: list[str] = [explicit] if explicit else []

    if os.name == "nt":
        try:
            completed = subprocess.run(
                [
                    "powershell",
                    "-NoProfile",
                    "-Command",
                    "Get-CimInstance Win32_Process -Filter \"Name='terminal64.exe'\" | Select-Object -ExpandProperty ExecutablePath",
                ],
                capture_output=True,
                text=True,
                timeout=8,
            )
        except (OSError, subprocess.TimeoutExpired):
            completed = None

        if completed is not None:
            for line in completed.stdout.splitlines():
                path = line.strip().strip('"')
                if path.lower().endswith("terminal64.exe") and path not in paths:
                    paths.append(path)

    if not paths:
        paths.append("")

    TERMINAL_PATHS = paths
    TERMINAL_PATHS_AT = now
    return list(paths)


def attach_login() -> bool:
    global LAST_NOTICE, SYMBOL_CACHE

    login = expected_login()
    password = os.environ.get("MT5_PASSWORD", "")
    server = os.environ.get("MT5_SERVER", "")
    seen = None

    for path in running_terminal_paths():
        try:
            mt5.shutdown()
        except Exception:
            pass

        attached = mt5.initialize(path) if path else mt5.initialize()
        if not attached:
            continue

        info = mt5.account_info()
        if info is not None and login_matches(info):
            SYMBOL_CACHE = None
            print(f"Desk connector: attached to login {info.login} on {info.server}", flush=True)
            return True

        if info is not None:
            seen = info

        if not login or password == "":
            continue

        if mt5.login(int(login), password=password, server=server) and login_matches(mt5.account_info()):
            info = mt5.account_info()
            SYMBOL_CACHE = None
            print(f"Desk connector: attached to login {info.login} on {info.server}", flush=True)
            return True

    if time.time() - LAST_NOTICE > 8:
        LAST_NOTICE = time.time()
        if seen is not None and login:
            print(
                f"Desk connector: open terminal is login {seen.login} on {seen.server}, not {login}. Leave the {login} terminal logged in.",
                flush=True,
            )
        else:
            print(f"Desk connector: could not attach to MT5 login {login or 'the open terminal'}.", flush=True)

    return False


def ensure_terminal() -> bool:
    global LAST_ATTACHED, LAST_TRY

    if mt5 is None:
        return False

    now = time.time()

    try:
        info = mt5.account_info()
        connected = mt5.terminal_info() is not None and info is not None
    except Exception:
        connected = False
        info = None

    if connected and login_matches(info):
        LAST_ATTACHED = now
        return True

    if now - LAST_TRY < 2:
        return False

    if not terminal_process_running():
        return False

    LAST_TRY = now
    if not attach_login():
        return False

    LAST_ATTACHED = time.time()
    return True


BROKER_ALIASES = {
    "BTCUSD": ["BTCUSDT"],
    "ETHUSD": ["ETHUSDT"],
    "XAUUSD": ["GOLD"],
    "XAGUSD": ["SILVER"],
    "XTIUSD": ["USOUSD", "UKOUSD", "USOIL", "UKOIL", "WTI"],
    "US30": ["DJ30"],
    "NAS100": ["NDX100", "USTEC", "US100", "NDX"],
}


def norm_symbol(value: str) -> str:
    return re.sub(r"[^A-Z0-9]", "", value.upper())


def broker_symbols() -> list:
    global SYMBOL_CACHE, SYMBOL_CACHE_AT

    if SYMBOL_CACHE is not None and time.time() - SYMBOL_CACHE_AT < 30:
        return SYMBOL_CACHE

    SYMBOL_CACHE = list(mt5.symbols_get() or [])
    SYMBOL_CACHE_AT = time.time()
    return SYMBOL_CACHE


def resolve_symbol(name: str) -> str | None:
    targets = [norm_symbol(name)]
    for alias in BROKER_ALIASES.get(name.upper(), []):
        token = norm_symbol(alias)
        if token not in targets:
            targets.append(token)

    best_name = None
    best_extra = 99
    for symbol in broker_symbols():
        key = norm_symbol(symbol.name)
        for target in targets:
            if not key.startswith(target):
                continue
            extra = len(key) - len(target)
            if extra > 4 or extra > best_extra:
                continue
            best_extra = extra
            best_name = symbol.name
            break

    if best_name is None:
        return None

    if not mt5.symbol_select(best_name, True):
        return None

    tick = mt5.symbol_info_tick(best_name)
    if tick is None or not tick.bid:
        return None

    return best_name


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
    global RESOLVED, RESOLVED_MAP, RESOLVED_FOR, LAST_RESOLVE

    wanted = tuple(SYMBOLS)
    complete = bool(RESOLVED) and RESOLVED_FOR == wanted and len(RESOLVED) >= len(wanted)
    if complete:
        return RESOLVED

    if RESOLVED and RESOLVED_FOR == wanted and time.time() - LAST_RESOLVE < 5:
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
    LAST_RESOLVE = time.time()
    missing = [name for name in SYMBOLS if name.upper() not in mapping.values()]
    print(f"Desk connector: watching {len(names)} symbols: {', '.join(mapping.values()) or 'none'}", flush=True)
    if missing:
        print(f"Desk connector: no rate yet for {', '.join(missing)}", flush=True)
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


def comment_of(desk_ticket: str) -> str:
    return str(desk_ticket)[:31]


def find_ticket(desk_ticket: str, pending: bool) -> int | None:
    comment = comment_of(desk_ticket)
    rows = mt5.orders_get() if pending else mt5.positions_get()
    if not rows or comment == "":
        return None

    for row in rows:
        if int(getattr(row, "magic", 0) or 0) != MAGIC:
            continue
        if str(getattr(row, "comment", "") or "") == comment:
            return int(row.ticket)

    return None


def filling_modes(symbol: str) -> list[int]:
    info = mt5.symbol_info(symbol)
    mode = int(getattr(info, "filling_mode", 0) or 0) if info is not None else 0
    modes: list[int] = []
    if mode & 2:
        modes.append(mt5.ORDER_FILLING_IOC)
    if mode & 1:
        modes.append(mt5.ORDER_FILLING_FOK)
    modes.append(mt5.ORDER_FILLING_RETURN)
    unique: list[int] = []
    for mode_id in modes:
        if mode_id not in unique:
            unique.append(mode_id)
    return unique


def send_request(request: dict, symbol: str):
    last = None
    modes = filling_modes(symbol)
    cached = FILLING.get(symbol)
    if cached is not None:
        modes = [cached, *[mode for mode in modes if mode != cached]]

    for filling in modes:
        request["type_filling"] = filling
        last = mt5.order_send(request)
        if last is not None and int(last.retcode) != 10030:
            if trade_done(last):
                FILLING[symbol] = filling
            return last
    return last


def result_error(result) -> str:
    if result is None:
        code, message = mt5.last_error()
        return f"{code} {message}".strip()

    comment = getattr(result, "comment", "") or ""
    return f"{int(result.retcode)} {comment}".strip()


def trade_done(result) -> bool:
    if result is None:
        return False

    done = {getattr(mt5, "TRADE_RETCODE_DONE", 10009), 10010}
    return int(result.retcode) in done


def command_result(cmd: dict, ok: bool, ticket: int | None = None, price: float | None = None, error: str = "") -> dict:
    return {
        "id": cmd.get("id"),
        "ok": ok,
        "mt5_ticket": str(ticket) if ticket else None,
        "price": price,
        "error": error,
    }


def send_open(cmd: dict) -> dict:
    desk = str(cmd.get("desk_ticket") or "")
    existing = find_ticket(desk, pending=False) or find_ticket(desk, pending=True)
    if existing:
        return command_result(cmd, True, existing)

    symbol = resolve_symbol(str(cmd.get("symbol") or ""))
    if not symbol:
        return command_result(cmd, False, error="Symbol is not on this MT5.")

    tick = mt5.symbol_info_tick(symbol)
    if tick is None or not tick.bid:
        return command_result(cmd, False, error="No price for this symbol.")

    side = str(cmd.get("side") or "buy")
    order_type = str(cmd.get("order_type") or "market")
    volume = round(float(cmd.get("volume") or 0), 2)
    if volume < 0.01:
        return command_result(cmd, False, error="Volume is too small.")

    pending = {
        "buy_limit": mt5.ORDER_TYPE_BUY_LIMIT,
        "sell_limit": mt5.ORDER_TYPE_SELL_LIMIT,
        "buy_stop": mt5.ORDER_TYPE_BUY_STOP,
        "sell_stop": mt5.ORDER_TYPE_SELL_STOP,
    }

    if order_type in ("market", ""):
        action = mt5.TRADE_ACTION_DEAL
        kind = mt5.ORDER_TYPE_BUY if side == "buy" else mt5.ORDER_TYPE_SELL
        price = float(tick.ask if side == "buy" else tick.bid)
    elif order_type in pending:
        action = mt5.TRADE_ACTION_PENDING
        kind = pending[order_type]
        price = float(cmd.get("price") or 0)
        if price <= 0:
            return command_result(cmd, False, error="Pending price is missing.")
    else:
        return command_result(cmd, False, error="Unknown order type.")

    request = {
        "action": action,
        "symbol": symbol,
        "volume": volume,
        "type": kind,
        "price": price,
        "sl": float(cmd.get("stop_loss") or 0),
        "tp": float(cmd.get("take_profit") or 0),
        "deviation": 30,
        "magic": MAGIC,
        "comment": comment_of(desk),
        "type_time": mt5.ORDER_TIME_GTC,
    }
    result = send_request(request, symbol)
    if not trade_done(result):
        return command_result(cmd, False, error=result_error(result))

    if action == mt5.TRADE_ACTION_PENDING:
        ticket = find_ticket(desk, pending=True) or int(getattr(result, "order", 0) or 0)
    else:
        ticket = find_ticket(desk, pending=False) or int(getattr(result, "position", 0) or 0) or int(getattr(result, "order", 0) or 0)
    fill = float(getattr(result, "price", 0) or price)
    print(f"Desk connector: opened {desk} on MT5 {ticket}", flush=True)
    return command_result(cmd, True, ticket or None, fill)


def send_close(cmd: dict, cancel: bool) -> dict:
    desk = str(cmd.get("desk_ticket") or "")
    given = int(cmd.get("mt5_ticket") or 0) or None

    if not cancel and given:
        rows = mt5.positions_get(ticket=int(given))
        if rows:
            return close_position(cmd, rows[0], desk)
        if rows is not None:
            found = find_ticket(desk, pending=False)
            if found:
                matched = mt5.positions_get(ticket=int(found))
                if matched:
                    return close_position(cmd, matched[0], desk)
            return command_result(cmd, True, int(given))

    position_ticket = None if cancel else (given or find_ticket(desk, pending=False))
    order_ticket = find_ticket(desk, pending=True)

    if cancel or (position_ticket is None and order_ticket):
        target = order_ticket or given
        if not target:
            return command_result(cmd, False, error="Pending order is not on MT5 yet.")

        result = mt5.order_send({"action": mt5.TRADE_ACTION_REMOVE, "order": int(target)})
        if not trade_done(result):
            return command_result(cmd, False, error=result_error(result))

        print(f"Desk connector: removed {desk} on MT5 {target}", flush=True)
        return command_result(cmd, True, int(target))

    if not position_ticket:
        return command_result(cmd, False, error="Position is not on MT5 yet.")

    rows = mt5.positions_get(ticket=int(position_ticket))
    if not rows:
        return command_result(cmd, True, int(position_ticket))

    return close_position(cmd, rows[0], desk)


def close_position(cmd: dict, pos, desk: str) -> dict:
    tick = mt5.symbol_info_tick(pos.symbol)
    if tick is None:
        return command_result(cmd, False, error="No price to close.")

    buy = int(pos.type) == int(getattr(mt5, "POSITION_TYPE_BUY", 0))
    request = {
        "action": mt5.TRADE_ACTION_DEAL,
        "symbol": pos.symbol,
        "volume": float(pos.volume),
        "type": mt5.ORDER_TYPE_SELL if buy else mt5.ORDER_TYPE_BUY,
        "position": int(pos.ticket),
        "price": float(tick.bid if buy else tick.ask),
        "deviation": 30,
        "magic": MAGIC,
        "comment": comment_of(desk),
        "type_time": mt5.ORDER_TIME_GTC,
    }
    result = send_request(request, pos.symbol)
    if not trade_done(result):
        return command_result(cmd, False, error=result_error(result))

    print(f"Desk connector: closed {desk} on MT5 {pos.ticket}", flush=True)
    return command_result(cmd, True, int(pos.ticket), float(getattr(result, "price", 0) or 0))


def send_modify(cmd: dict) -> dict:
    desk = str(cmd.get("desk_ticket") or "")
    sl = float(cmd.get("stop_loss") or 0)
    tp = float(cmd.get("take_profit") or 0)
    given = int(cmd.get("mt5_ticket") or 0) or None
    position_ticket = find_ticket(desk, pending=False) or given

    if position_ticket:
        rows = mt5.positions_get(ticket=int(position_ticket))
        if rows:
            pos = rows[0]
            result = mt5.order_send({
                "action": mt5.TRADE_ACTION_SLTP,
                "symbol": pos.symbol,
                "position": int(pos.ticket),
                "sl": sl,
                "tp": tp,
            })
            if not trade_done(result):
                return command_result(cmd, False, error=result_error(result))
            return command_result(cmd, True, int(pos.ticket))

    order_ticket = find_ticket(desk, pending=True)
    if order_ticket:
        orders = mt5.orders_get(ticket=int(order_ticket))
        if orders:
            order = orders[0]
            result = mt5.order_send({
                "action": mt5.TRADE_ACTION_MODIFY,
                "order": int(order.ticket),
                "price": float(order.price_open),
                "sl": sl,
                "tp": tp,
                "type_time": mt5.ORDER_TIME_GTC,
            })
            if not trade_done(result):
                return command_result(cmd, False, error=result_error(result))
            return command_result(cmd, True, int(order.ticket))

    return command_result(cmd, False, error="Order is not on MT5 yet.")


def execute_command(cmd: dict) -> dict:
    if mt5 is None or not ensure_terminal():
        return command_result(cmd, False, error="MT5 terminal is not attached.")

    action = str(cmd.get("action") or "")
    if action == "open":
        return send_open(cmd)
    if action == "close":
        return send_close(cmd, cancel=False)
    if action == "cancel":
        return send_close(cmd, cancel=True)
    if action == "modify":
        return send_modify(cmd)

    return command_result(cmd, False, error="Unknown command.")


def run_commands(commands: list) -> None:
    with COMMAND_LOCK:
        _run_commands(commands)


def _run_commands(commands: list) -> None:
    for cmd in commands:
        if not isinstance(cmd, dict):
            continue

        cid = str(cmd.get("id", ""))
        if cid == "" or cid in INFLIGHT:
            continue

        INFLIGHT.add(cid)
        try:
            result = execute_command(cmd)
            laravel_post("/api/forex/connector/executions", {
                "mt5_account_id": ACCOUNT_ID,
                "results": [result],
            })
            if not result.get("ok"):
                INFLIGHT.discard(cid)
                print(f"Desk connector order rejected: {result.get('error')}", flush=True)
        except Exception as error:
            INFLIGHT.discard(cid)
            print(f"Desk connector order failed: {error}", flush=True)


def report_fills(resting: list) -> None:
    results = []

    for item in resting:
        if not isinstance(item, dict):
            continue

        ticket = int(item.get("mt5_ticket") or 0)
        desk = str(item.get("desk_ticket") or "")
        if ticket <= 0 or desk == "":
            continue

        still = mt5.orders_get(ticket=ticket)
        if still is None or still:
            continue

        comment = comment_of(desk)
        found = None
        for pos in mt5.positions_get() or []:
            if int(getattr(pos, "magic", 0) or 0) != MAGIC:
                continue
            if str(getattr(pos, "comment", "") or "") == comment:
                found = pos
                break

        if found is None:
            continue

        results.append({
            "position_id": item.get("position_id"),
            "ok": True,
            "filled": True,
            "mt5_ticket": str(int(found.ticket)),
            "price": float(found.price_open),
        })

    if results:
        laravel_post("/api/forex/connector/executions", {
            "mt5_account_id": ACCOUNT_ID,
            "results": results,
        })


def command_loop() -> None:
    while True:
        try:
            if ensure_terminal():
                reply = laravel_post("/api/forex/connector/commands", {"mt5_account_id": ACCOUNT_ID})
                commands = reply.get("commands") if isinstance(reply, dict) else None
                resting = reply.get("resting") if isinstance(reply, dict) else None
                if isinstance(commands, list) and commands:
                    run_commands(commands)
                if isinstance(resting, list) and resting:
                    report_fills(resting)
        except Exception:
            time.sleep(0.05)

        time.sleep(0.02)


def publish_loop() -> None:
    while True:
        payload = PUBLISH.get()
        try:
            reply = laravel_post("/api/forex/connector/sync", payload)
            if isinstance(reply, dict):
                apply_watch_symbols(reply.get("watch_symbols"))
                commands = reply.get("commands")
                if isinstance(commands, list) and commands:
                    run_commands(commands)
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
    if key.startswith(("XAU", "XAG", "US30", "NAS", "BTC", "XTI", "USO", "UKO")):
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
        try:
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
        except (BrokenPipeError, ConnectionAbortedError, ConnectionResetError):
            return

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
    threading.Thread(target=command_loop, name="desk-commands", daemon=True).start()
    threading.Thread(target=publish_loop, name="desk-publish", daemon=True).start()
    bridge_loop()


if __name__ == "__main__":
    main()
