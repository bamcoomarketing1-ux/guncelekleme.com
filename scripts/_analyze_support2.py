import re
from pathlib import Path

s = Path(__file__).resolve().parents[1].joinpath("public/assets/SupportHistory-lm9i9r3E.js").read_text(encoding="utf-8")
for pat in ["m.value", "role", "sender", "message", "bot"]:
    for m in re.finditer(r".{0,60}" + pat + r".{0,60}", s):
        if "m.value" in pat or pat in m.group():
            print(m.group()[:180])
            break
