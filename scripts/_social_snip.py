from pathlib import Path
s = Path(__file__).resolve().parents[1].joinpath("public/assets/index-ChvzUPTI.js").read_text(encoding="utf-8")
idx = s.find("YENI SOSYAL")
print(s[idx-200:idx+1200] if idx>=0 else "not found")
