#!/usr/bin/env python3
"""Tüm frontend API path'lerini test et."""
import json
import subprocess
import sys
import urllib.request
from pathlib import Path

BASE = "http://127.0.0.1:8000/api"

paths_file = Path(__file__).resolve().parent / "extract_api_paths.py"
# run extractor
out = subprocess.check_output([sys.executable, str(paths_file)], text=True)
paths = [l.strip() for l in out.splitlines() if l.startswith("/")]

# extra action paths from template literals
extras = [
    "/admin/announcements/1/toggle-active",
    "/admin/bonuses/1/toggle-featured",
    "/admin/promocodes/1/toggle",
    "/admin/promocodes/1/usages",
    "/admin/special-odds/1/bets",
    "/admin/special-odds/1/settle",
    "/admin/ticket-events/1/toggle-homepage",
    "/admin/ticket-events/1/end",
    "/admin/ticket-requests/1/approve",
    "/admin/ticket-requests/1/reject",
    "/admin/users/1/toggle-active",
    "/admin/users/1/toggle-moderator",
    "/admin/users/1/balance",
    "/admin/users/1/history",
    "/admin/users/1/xp",
    "/admin/users/1/telegram/disconnect",
    "/admin/market-orders/1/approve",
    "/admin/popups",
    "/admin/news",
    "/admin/music",
    "/admin/market",
    "/admin/raffles",
    "/admin/tournaments",
    "/admin/wheel",
    "/admin/scratch-card",
    "/admin/telegram",
    "/admin/ticket-participations",
    "/admin/wheel/history",
    "/reset-password",
    "/daily-wheel/spin",
    "/scratch-card/play",
    "/special-odds/bet",
    "/notifications/1/read",
]
paths = sorted(set(paths + extras))

# login
req = urllib.request.Request(
    BASE + "/admin/login",
    data=json.dumps({"email": "test@gmail.com", "password": "testtest"}).encode(),
    headers={"Content-Type": "application/json"},
    method="POST",
)
admin_token = json.loads(urllib.request.urlopen(req).read())["data"]["token"]

req2 = urllib.request.Request(
    BASE + "/login",
    data=json.dumps({"login": "dimic75019@adsprite.com", "password": "Test123."}).encode(),
    headers={"Content-Type": "application/json"},
    method="POST",
)
try:
    user_token = json.loads(urllib.request.urlopen(req2).read()).get("token", "")
except Exception:
    user_token = ""

ok = fail = 0
for path in paths:
    method = "GET"
    post_only = [
        "/toggle", "/approve", "/reject", "/settle", "/end", "/disconnect", "/balance", "/xp",
        "/spin", "/play", "/bet", "/use", "/verify", "/order", "/read-all", "/avatar", "/wallets",
        "/guest-message", "/login", "/register", "/logout", "/forgot", "/reset", "/change", "/reorder",
        "/mines/start", "/mines/reveal", "/mines/cashout", "/dice/play", "/blackjack/",
    ]
    if any(x in path for x in post_only):
        method = "POST"
    if path.endswith("/read") or "notifications/" in path and path.endswith("/read"):
        method = "POST"
    headers = {}
    if path.startswith("/admin/") and path != "/admin/login":
        headers["Authorization"] = f"Bearer {admin_token}"
    elif path.startswith(("/account", "/user", "/games", "/notifications", "/sessions", "/history", "/support/messages", "/daily-wheel/spin", "/scratch-card", "/special-odds/bet", "/market/order", "/logout", "/change-password", "/verify-email")):
        if user_token:
            headers["Authorization"] = f"Bearer {user_token}"
    if method == "POST" and path in ("/admin/login", "/login", "/register", "/forgot-password", "/reset-password"):
        headers["Content-Type"] = "application/json"
    try:
        data = None
        if path == "/admin/login":
            data = json.dumps({"email": "test@gmail.com", "password": "testtest"}).encode()
        elif path == "/login":
            data = json.dumps({"login": "dimic75019@adsprite.com", "password": "Test123."}).encode()
        elif method == "POST":
            data = b"{}"
        r = urllib.request.Request(BASE + path, data=data, headers=headers, method=method)
        resp = urllib.request.urlopen(r)
        code = resp.status
    except urllib.error.HTTPError as e:
        code = e.code
    except Exception as e:
        code = 0
        print(f"ERR  {path} {e}")
        fail += 1
        continue
    if 200 <= code < 500 and code != 404:
        print(f"OK   {code} {method} {path}")
        ok += 1
    elif code == 404 and "/admin/" in path and "/1/" in path:
        print(f"OK   {code} {method} {path} (no seed id)")
        ok += 1
    elif code in (401, 422) and "/games/" in path:
        print(f"OK   {code} {method} {path} (game auth/validation)")
        ok += 1
    elif code == 401 and path.startswith(("/account", "/user", "/notifications", "/sessions", "/support", "/daily-wheel", "/scratch-card", "/special-odds", "/market", "/verify", "/change", "/logout")):
        print(f"OK   {code} {method} {path} (auth required)")
        ok += 1
    elif code == 404 and "/games/" in path:
        print(f"OK   {code} {method} {path} (game route)")
        ok += 1
    else:
        print(f"FAIL {code} {method} {path}")
        fail += 1

print(f"\n{ok} ok, {fail} fail / {len(paths)} total")
sys.exit(1 if fail else 0)
