#!/usr/bin/env python3
"""index-ChvzUPTI.js içinde referans verilen tüm Vite chunk dosyalarını indir."""
from __future__ import annotations

import json
import re
import ssl
import sys
import time
import urllib.error
import urllib.request
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
ASSETS = ROOT / "resources" / "frontend" / "assets"
MAIN_JS = ASSETS / "index-ChvzUPTI.js"

ORIGINS = [
    "https://slotdeneme2025.com",
    "https://alisulasyon51.com",
    "https://alijhgfdjhgjhdfhjgdfjhgjdfg.site",
]

CTX = ssl.create_default_context()
UA = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0"


def chunk_paths(js_text: str) -> list[str]:
    found = set(re.findall(r"assets/[A-Za-z0-9_.-]+\.(?:js|css)", js_text))
    for name in re.findall(r"[A-Za-z][A-Za-z0-9_.-]*-[A-Za-z0-9_-]{6,8}\.(?:js|css)", js_text):
        found.add(f"assets/{name}")
    return sorted(found)


def fetch(url: str) -> tuple[int, bytes]:
    req = urllib.request.Request(url, headers={"User-Agent": UA, "Accept": "*/*"})
    try:
        with urllib.request.urlopen(req, context=CTX, timeout=30) as r:
            return r.status, r.read()
    except urllib.error.HTTPError as e:
        return e.code, e.read() if e.fp else b""


def main() -> int:
    if not MAIN_JS.exists():
        print(f"Ana bundle yok: {MAIN_JS}", file=sys.stderr)
        return 1

    ASSETS.mkdir(parents=True, exist_ok=True)
    paths = chunk_paths(MAIN_JS.read_text(encoding="utf-8", errors="ignore"))
    # Ana bundle + css zaten var; eksik olanları indir
    paths = [p for p in paths if not (ASSETS / Path(p).name).exists()]

    report = {"requested": len(paths), "downloaded": [], "failed": []}
    print(f"Eksik chunk: {len(paths)}")

    for rel in paths:
        name = Path(rel).name
        dest = ASSETS / name
        ok = False
        for origin in ORIGINS:
            url = f"{origin}/{rel}"
            status, body = fetch(url)
            if status == 200 and len(body) > 50:
                dest.write_bytes(body)
                report["downloaded"].append({"file": name, "url": url, "bytes": len(body)})
                print(f"OK  {name} ({len(body)} bytes) <- {origin}")
                ok = True
                break
            time.sleep(0.15)
        if not ok:
            report["failed"].append(name)
            print(f"FAIL {name}")

    manifest = ROOT / "resources" / "frontend" / "chunks_manifest.json"
    manifest.write_text(json.dumps(report, indent=2), encoding="utf-8")

    total = len(list(ASSETS.glob("*")))
    print(f"\nassets/ toplam dosya: {total}")
    if report["failed"]:
        print(f"Hâlâ eksik: {len(report['failed'])} — {', '.join(report['failed'][:8])}")
        return 1

    verify = ROOT / "scripts" / "verify_frontend_chunks.py"
    if verify.exists():
        import subprocess
        subprocess.run([sys.executable, str(verify)], check=False)

    print("Tüm chunk dosyaları hazır.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
