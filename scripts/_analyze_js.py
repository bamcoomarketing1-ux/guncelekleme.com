import re
from pathlib import Path

root = Path(__file__).resolve().parents[1]

files = {
    "SupportHistory": root / "public/assets/SupportHistory-lm9i9r3E.js",
    "NewsManagement": root / "public/assets/NewsManagement-DcFS_Q5z.js",
    "MarketManagement": root / "public/assets/MarketManagement-BLGnBLI7.js",
    "index": root / "public/assets/index-ChvzUPTI.js",
}

for name, path in files.items():
    s = path.read_text(encoding="utf-8")
    print(f"\n=== {name} ===")
    if name == "SupportHistory":
        for m in re.finditer(r"/admin/[\w\-/{}]+", s):
            print(m.group())
    if name == "NewsManagement":
        idx = s.find("overflow-hidden")
        if idx >= 0:
            print("overflow:", s[idx - 100 : idx + 200])
        idx = s.find('select"')
        if idx >= 0:
            print("select:", s[idx - 50 : idx + 300])
    if name == "MarketManagement":
        idx = s.find("ie=[")
        print("img src:", s[idx : idx + 120] if idx >= 0 else "n/a")
    if name == "index":
        idx = s.find("PLATFORM")
        if idx >= 0:
            print(s[idx - 100 : idx + 400])
