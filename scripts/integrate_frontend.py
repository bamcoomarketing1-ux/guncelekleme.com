#!/usr/bin/env python3
"""Frontend'i public/ içine yayınla (kaynak: resources/frontend)."""
from __future__ import annotations

import re
import shutil
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
SRC = ROOT / "resources" / "frontend"
PUBLIC = ROOT / "public"
STORAGE_DST = ROOT / "storage" / "app" / "public"


def patch_js(text: str) -> str:
    text = re.sub(r'baseURL:"https://\\u200b[^"]+"', 'baseURL:"/api"', text)
    text = text.replace("alijhgfdjhgjhdfhjgdfjhgjdfg.site/api", "/api")
    text = re.sub(r'baseURL:"https://[^"]*?/api"', 'baseURL:"/api"', text)
    return text


def strip_cloudflare_beacon(text: str) -> str:
    text = re.sub(r'<script defer src="https://static\.cloudflareinsights\.com[^<]+</script>\s*', "", text)
    text = re.sub(r'<script[^>]*cloudflareinsights[^>]*></script>\s*', "", text, flags=re.I)
    return text


def main():
    if not SRC.exists():
        fallback = ROOT.parent / "alisulasyon51" / "backup" / "frontend"
        if fallback.exists():
            shutil.copytree(fallback, SRC, dirs_exist_ok=True)
        else:
            raise SystemExit(f"Frontend bulunamadı: {SRC}")

    chunks_script = ROOT / "scripts" / "download_frontend_chunks.py"
    if chunks_script.exists() and "--no-chunks" not in sys.argv:
        subprocess.run([sys.executable, str(chunks_script)], check=False)

    for item in ["assets", "index.html", "robots.txt", "sitemap.xml", "manifest.json", "favicon.ico"]:
        s = SRC / item
        if not s.exists():
            continue
        d = PUBLIC / item
        if s.is_dir():
            if d.exists():
                shutil.rmtree(d)
            shutil.copytree(s, d)
        else:
            shutil.copy2(s, d)

    js = PUBLIC / "assets" / "index-ChvzUPTI.js"
    if js.exists():
        js.write_text(patch_js(js.read_text(encoding="utf-8")), encoding="utf-8")

    html = PUBLIC / "index.html"
    if html.exists():
        t = html.read_text(encoding="utf-8")
        t = strip_cloudflare_beacon(t)
        t = t.replace("<title>Blonk Digital</title>", "<title>Alisulasyon</title>")
        t = t.replace("<title>Alisulasyon Clone</title>", "<title>Alisulasyon</title>")
        html.write_text(t, encoding="utf-8")

    for name in ("manifest.json", "sitemap.xml"):
        path = PUBLIC / name
        if path.exists():
            t = strip_cloudflare_beacon(path.read_text(encoding="utf-8"))
            path.write_text(t, encoding="utf-8")

    src_html = SRC / "index.html"
    if src_html.exists():
        t = strip_cloudflare_beacon(src_html.read_text(encoding="utf-8"))
        t = t.replace("<title>Blonk Digital</title>", "<title>Alisulasyon</title>")
        src_html.write_text(t, encoding="utf-8")

    download_script = ROOT / "scripts" / "download_storage.py"
    if download_script.exists() and "--no-storage" not in sys.argv:
        subprocess.run([sys.executable, str(download_script)], check=False)

    verify_script = ROOT / "scripts" / "verify_frontend_chunks.py"
    if verify_script.exists():
        subprocess.run([sys.executable, str(verify_script)], check=False)

    print("Frontend resources/frontend -> public/ hazır")


if __name__ == "__main__":
    main()
