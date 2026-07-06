from pathlib import Path
import re
for fname in ['TournamentManagement-DQCiRZae.js','TournamentsView-Dehd14vd.js']:
 s=Path(rf'C:\Users\Developer\Desktop\sad\Yeni klasör\alisulasyon-laravel\public\assets\{fname}').read_text(encoding='utf-8')
 print('====', fname, '====')
 for pat in ['/admin/tournaments', 'name:', 'size:', 'POST', 'participants']:
  i=s.find(pat)
  if i>=0:
   print(s[i-80:i+200])
   print('---')
