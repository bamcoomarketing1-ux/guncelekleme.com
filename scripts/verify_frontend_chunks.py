#!/usr/bin/env python3
"""index-ChvzUPTI.js ve tüm lazy chunk referanslarını assets ile karşılaştır."""
from __future__ import annotations

import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent

PREFixed = re.compile(r"assets/[A-Za-z0-9_.-]+\.(?:js|css)")
BARE = re.compile(r"[A-Za-z][A-Za-z0-9_.-]*-[A-Za-z0-9_-]{6,8}\.(?:js|css)")
QUOTED = re.compile(r'["\']([A-Za-z][A-Za-z0-9_.-]*-[A-Za-z0-9_-]{6,8}\.(?:js|css))["\']')


def refs_from_text(text: str) -> set[str]:
    found = {Path(p).name for p in PREFixed.findall(text)}
    found.update(BARE.findall(text))
    found.update(QUOTED.findall(text))
    return found


def all_referenced(assets_dir: Path) -> set[str]:
    refs: set[str] = set()
    main = assets_dir / "index-ChvzUPTI.js"
    if main.exists():
        refs |= refs_from_text(main.read_text(encoding="utf-8", errors="ignore"))
    for js in assets_dir.glob("*.js"):
        refs |= refs_from_text(js.read_text(encoding="utf-8", errors="ignore"))
    for css in assets_dir.glob("*.css"):
        refs |= refs_from_text(css.read_text(encoding="utf-8", errors="ignore"))
    return refs


def check_folder(label: str, assets_dir: Path) -> int:
    if not assets_dir.is_dir():
        print(f"[{label}] klasör yok: {assets_dir}")
        return 1

    refs = all_referenced(assets_dir)
    on_disk = {p.name for p in assets_dir.iterdir() if p.is_file()}
    missing = sorted(refs - on_disk)

    print(f"[{label}] toplam referans: {len(refs)}, disk: {len(on_disk)}, eksik: {len(missing)}")
    for name in missing:
        print(f"  EKSIK: {name}")

    return 1 if missing else 0


def main() -> int:
    code = 0
    for rel in ("resources/frontend/assets", "public/assets"):
        code |= check_folder(rel, ROOT / rel.replace("/", "\\"))
    if code == 0:
        print("\nTüm JS/CSS chunk dosyaları mevcut.")
    return code


if __name__ == "__main__":
    raise SystemExit(main())
