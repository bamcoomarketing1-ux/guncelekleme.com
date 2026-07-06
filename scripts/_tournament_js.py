from pathlib import Path
import re
s=Path(r'C:\Users\Developer\Desktop\sad\Yeni klasör\alisulasyon-laravel\public\assets\TournamentBracket-C_oVp4vx.js').read_text(encoding='utf-8')
for pat in [r'post\([^)]+\)', r'name:', r'size:', r'participants', r'start', r'winner']:
 for m in re.finditer(pat, s):
  print(s[max(0,m.start()-60):m.end()+100])
  print('---')
  break
