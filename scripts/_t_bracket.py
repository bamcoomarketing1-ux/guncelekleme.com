from pathlib import Path
s=Path(r'C:\Users\Developer\Desktop\sad\Yeni klasör\alisulasyon-laravel\public\assets\TournamentBracket-C_oVp4vx.js').read_text(encoding='utf-8')
for pat in ['start', 'winner', 'participants/']:
 i=0
 while True:
  j=s.find(pat, i)
  if j<0: break
  print(s[j-40:j+120])
  print('---')
  i=j+len(pat)
  if i>80000: break
