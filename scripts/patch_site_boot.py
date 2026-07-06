#!/usr/bin/env python3
"""Apply window.__SITE_BOOT__ support to settings store in bundled JS."""

from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
TARGETS = [
    ROOT / "public/assets/index-ChvzUPTI.js",
    ROOT / "resources/frontend/assets/index-ChvzUPTI.js",
]

OLD_START = 'As=cr("settings",()=>{const e=N({site_name:"Nexu V1",'
NEW_START = (
    'As=cr("settings",()=>{const _b=typeof window<"u"&&window.__SITE_BOOT__||null;'
    'const e=N({site_name:_b?.site_name||"Nexu V1",'
)

OLD_RETURN = (
    'index_primary_color:e.value.index_primary_color||"#f30f48"})};'
    'return{settings:e,loading:t,fetchSettings:'
)
NEW_RETURN = (
    'index_primary_color:e.value.index_primary_color||"#f30f48"})};'
    '_b&&r(_b);return{settings:e,loading:t,fetchSettings:'
)


def patch_file(path: Path) -> list[str]:
    text = path.read_text(encoding="utf-8")
    changes: list[str] = []

    for name, old, new in [
        ("settings boot start", OLD_START, NEW_START),
        ("settings boot apply", OLD_RETURN, NEW_RETURN),
    ]:
        if old not in text:
            if new in text:
                changes.append(f"{name}: already patched")
            else:
                changes.append(f"{name}: pattern not found")
            continue
        text = text.replace(old, new, 1)
        changes.append(f"{name}: patched")

    path.write_text(text, encoding="utf-8")
    return changes


def main() -> None:
    for target in TARGETS:
        if not target.is_file():
            print(f"SKIP missing {target}")
            continue
        print(f"\n{target}:")
        for line in patch_file(target):
            print(f"  - {line}")


if __name__ == "__main__":
    main()
