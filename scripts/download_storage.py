#!/usr/bin/env python3
"""Backup storage_urls.txt dosyasından medya dosyalarını indir."""
from __future__ import annotations

import re
import sys
import urllib.error
import urllib.request
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
URLS_FILE = ROOT.parent / "alisulasyon51" / "backup" / "storage_urls.txt"
if not URLS_FILE.exists():
    URLS_FILE = ROOT.parent / "alisulasyon-clone" / "data" / "backup" / "storage_urls.txt"
DEST = ROOT / "storage" / "app" / "public"
MIN_BYTES = 80


def storage_path(url: str) -> Path | None:
    m = re.search(r"/storage/(.+)$", url.strip())
    if not m:
        return None
    return DEST / m.group(1).replace("/", "\\") if sys.platform == "win32" else DEST / m.group(1)


def download(url: str) -> tuple[bool, str]:
    path = storage_path(url)
    if not path:
        return False, "bad url"
    if path.exists() and path.stat().st_size >= MIN_BYTES:
        return True, "skip"
    path.parent.mkdir(parents=True, exist_ok=True)
    req = urllib.request.Request(url, headers={"User-Agent": "AlisulasyonBackup/1.0"})
    try:
        with urllib.request.urlopen(req, timeout=20) as resp:
            data = resp.read()
        if len(data) < MIN_BYTES:
            return False, f"small ({len(data)}b)"
        path.write_bytes(data)
        return True, f"ok ({len(data)}b)"
    except urllib.error.HTTPError as e:
        return False, f"http {e.code}"
    except Exception as e:
        return False, str(e)[:60]


def main() -> int:
    if not URLS_FILE.exists():
        print(f"storage_urls.txt bulunamadı: {URLS_FILE}")
        return 1

    urls = [u.strip() for u in URLS_FILE.read_text(encoding="utf-8").splitlines() if u.strip().startswith("http")]
    ok = skip = fail = 0
    for url in urls:
        success, note = download(url)
        if note == "skip":
            skip += 1
        elif success:
            ok += 1
            print(f"OK   {note} {url[-50:]}")
        else:
            fail += 1
            print(f"FAIL {note} {url[-60:]}")

    print(f"\nToplam: {len(urls)} | indirilen: {ok} | mevcut: {skip} | hata: {fail}")
    return 0 if ok + skip > 0 else 1


if __name__ == "__main__":
    raise SystemExit(main())
