#!/usr/bin/env python3
import re
from pathlib import Path

js = Path(__file__).resolve().parents[1] / "public/assets/index-ChvzUPTI.js"
text = js.read_text(encoding="utf-8", errors="ignore")

patterns = [
    r'["\'](/(?:admin|account|games|user|support|market|daily-wheel|notifications|sessions|settings|login|register|logout|verify|forgot|change|reset|leaderboard|bonuses|trial|ticket|raffles|tournaments|news|music|popup|social|special|promotions|scratch|wheel|telegram|history|banners|sliders|sponsors|announcements|promo)[a-z0-9\-/]*?)["\']',
    r'`(/(?:admin|account|games|user|support|market|daily-wheel|notifications|sessions|settings)[a-z0-9\-/]*?)\$\{',
]

paths = set()
for pat in patterns:
    for m in re.finditer(pat, text):
        p = re.sub(r'\$\{[^}]+\}', '{id}', m.group(1))
        if len(p) < 80 and not p.endswith('/'):
            paths.add(p.rstrip('/'))

for p in sorted(paths):
    print(p)
print(f"--- TOTAL {len(paths)}")
