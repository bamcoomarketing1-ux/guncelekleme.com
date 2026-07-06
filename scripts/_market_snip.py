from pathlib import Path
s = Path(__file__).resolve().parents[1].joinpath("public/assets/MarketManagement-BLGnBLI7.js").read_text(encoding="utf-8")
idx = s.find("M(l)")
print(s[idx-200:idx+400])
