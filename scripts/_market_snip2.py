from pathlib import Path
s = Path(__file__).resolve().parents[1].joinpath("public/assets/MarketManagement-BLGnBLI7.js").read_text(encoding="utf-8")
for needle in ['ie=["src"]', 'image_path', 'w-16 h-16 rounded-xl']:
    idx = s.find(needle)
    print('---', needle)
    print(s[idx-120:idx+200] if idx>=0 else 'NOT FOUND')
