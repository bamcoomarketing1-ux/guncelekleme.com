#!/usr/bin/env python3
"""Passive security check for alisulasyon51.com (owner verification)."""
from __future__ import annotations

import json
import ssl
import urllib.error
import urllib.request
from pathlib import Path

SITE = "https://alisulasyon51.com"
API = "https://alijhgfdjhgjhdfhjgdfjhgjdfg.site/api"
OUT = Path(__file__).resolve().parent / "live_security_report.json"
CTX = ssl.create_default_context()


def get(url: str, method: str = "GET", data: bytes | None = None, headers: dict | None = None) -> dict:
    h = {"User-Agent": "AlisulasyonSecurityProbe/1.0"}
    if headers:
        h.update(headers)
    req = urllib.request.Request(url, data=data, headers=h, method=method)
    try:
        with urllib.request.urlopen(req, timeout=20, context=CTX) as r:
            body = r.read(8000).decode("utf-8", "replace")
            return {"status": r.status, "headers": dict(r.headers), "body": body}
    except urllib.error.HTTPError as e:
        body = e.read(8000).decode("utf-8", "replace") if e.fp else ""
        return {"status": e.code, "headers": dict(e.headers), "body": body}
    except Exception as e:
        return {"status": None, "error": str(e), "body": ""}


def main():
    report: dict = {"site": SITE, "api": API, "findings": []}

    fe = get(SITE + "/")
    report["frontend_status"] = fe.get("status")
    report["title_blonk"] = "Blonk Digital" in fe.get("body", "")

    js = get(SITE + "/assets/index-ChvzUPTI.js")
    js_body = js.get("body", "")
    remote_api = "alijhgfdjhgjhdfhjgdfjhgjdfg.site" in js_body
    report["js_remote_api_exposed"] = remote_api
    if remote_api:
        report["findings"].append({"severity": "info", "issue": "API domain hâlâ JS bundle içinde görünüyor"})

    for path in ["/settings", "/banners", "/leaderboard"]:
        r = get(API + path)
        report[f"public{path.replace('/', '_')}"] = r.get("status")

    admin_users = get(API + "/admin/users")
    report["admin_users_unauth"] = admin_users.get("status")
    if admin_users.get("status") == 200:
        report["findings"].append({"severity": "critical", "issue": "/admin/users auth olmadan erişilebilir"})

    admin_dash = get(API + "/admin/dashboard")
    report["admin_dashboard_unauth"] = admin_dash.get("status")
    if admin_dash.get("status") == 200:
        report["findings"].append({"severity": "critical", "issue": "/admin/dashboard auth olmadan erişilebilir"})

    settings_post = get(
        API + "/settings",
        method="POST",
        data=b'{"site_name":"security_probe_test"}',
        headers={"Content-Type": "application/json"},
    )
    report["settings_post_unauth"] = settings_post.get("status")
    if settings_post.get("status") in (200, 201):
        report["findings"].append({"severity": "critical", "issue": "POST /settings auth gerektirmiyor — herkes site ayarını değiştirebilir"})

    login = get(
        API + "/admin/login",
        method="POST",
        data=json.dumps({"email": "test@gmail.com", "password": "testtest"}).encode(),
        headers={"Content-Type": "application/json"},
    )
    report["admin_login_test"] = {
        "status": login.get("status"),
        "has_token": "token" in login.get("body", "").lower(),
    }
    if login.get("status") == 200 and "token" in login.get("body", "").lower():
        report["findings"].append({"severity": "high", "issue": "Bilinen zayıf admin şifresi (test@gmail.com) hâlâ geçerli"})

    verify = get(
        API + "/verify-email",
        method="POST",
        data=b"{}",
        headers={"Content-Type": "application/json", "Authorization": "Bearer invalid"},
    )
    report["verify_email_invalid_token"] = verify.get("status")

    OUT.write_text(json.dumps(report, indent=2, ensure_ascii=False), encoding="utf-8")
    print(json.dumps(report, indent=2, ensure_ascii=False))
    print(f"\nSaved: {OUT}")


if __name__ == "__main__":
    main()
