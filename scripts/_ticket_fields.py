from pathlib import Path
s=Path(r'C:\Users\Developer\Desktop\sad\Yeni klasör\alisulasyon-laravel\public\assets\TicketDetailView-uTdU-kR9.js').read_text(encoding='utf-8')
for pat in ['user_tickets', 'ticket_number', 'leaderboard', 'approved_ticket']:
 i=0
 while True:
  j=s.find(pat, i)
  if j<0: break
  print(s[max(0,j-60):j+150])
  print('---')
  i=j+len(pat)
  if i>120000: break
