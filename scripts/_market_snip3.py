from pathlib import Path
s = Path(__file__).resolve().parents[1].joinpath("public/assets/MarketManagement-BLGnBLI7.js").read_text(encoding="utf-8")
idx = s.find('e("img"')
while idx >= 0:
    print(s[idx:idx+180])
    print('---')
    idx = s.find('e("img"', idx+1)
