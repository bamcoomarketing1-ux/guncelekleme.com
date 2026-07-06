from pathlib import Path
s=Path(r'C:\Users\Developer\Desktop\sad\Yeni klasör\alisulasyon-laravel\public\assets\SpecialOddsView-BKhmsouc.js').read_text(encoding='utf-8')
# find all img tags
import re
for m in re.finditer(r't\("img"[^)]+\)', s):
 print(m.group()[:200])
print('---functions using b---')
for m in re.finditer(r'.{0,30}\bb\b.{0,60}', s):
 if 'as b' not in m.group() and 'border' not in m.group():
  print(m.group())
