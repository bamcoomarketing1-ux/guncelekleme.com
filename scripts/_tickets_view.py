from pathlib import Path
s=Path(r'C:\Users\Developer\Desktop\sad\Yeni klasör\alisulasyon-laravel\public\assets\TicketsView-BaGiBzgx.js').read_text(encoding='utf-8')
for pat in ['ticket-events', 'status', 'ended', 'router', 'push']:
 i=s.find(pat)
 if i>=0:
  print(s[max(0,i-80):i+200])
  print('---')
