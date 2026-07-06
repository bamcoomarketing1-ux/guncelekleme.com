import re
from pathlib import Path

s = Path(__file__).resolve().parents[1].joinpath("public/assets/SupportHistory-lm9i9r3E.js").read_text(encoding="utf-8")
for fn in ["async", "get(", "conversations", "messages", "user_id"]:
    idx = 0
    while True:
        idx = s.find(fn, idx)
        if idx < 0:
            break
        print(s[max(0, idx - 80) : idx + 200])
        print("---")
        idx += len(fn)
