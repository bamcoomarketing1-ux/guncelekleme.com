from pathlib import Path
s=Path(r'C:\Users\Developer\Desktop\sad\Yeni klasör\alisulasyon-laravel\public\assets\TicketDetailView-uTdU-kR9.js').read_text(encoding='utf-8')
for pat in ['ticket-events', 'ticket-request', 'ticket-participation', 'Talep', 'description', 'note', 'message']:
 idx=0
 c=0
 while c<5:
  i=s.find(pat, idx)
  if i<0: break
  print('---', pat, '---')
  print(s[max(0,i-120):i+250])
  idx=i+len(pat)
  c+=1

print('\n=== NOTIFICATIONS ===')
s2=Path(r'C:\Users\Developer\Desktop\sad\Yeni klasör\alisulasyon-laravel\public\assets\AccountNotifications-BrZRm2sg.js').read_text(encoding='utf-8')
for pat in ['notifications', 'is_read', 'body', 'title', 'created_at', 'date']:
 i=s2.find(pat)
 if i>=0:
  print(s2[max(0,i-100):i+200])
  print('---')

print('\n=== MARKET ===')
s3=Path(r'C:\Users\Developer\Desktop\sad\Yeni klasör\alisulasyon-laravel\public\assets\MarketManagement-BLGnBLI7.js').read_text(encoding='utf-8')
for pat in ['image_path', 'image_url', 'placeholder', 'src:']:
 i=0
 while True:
  j=s3.find(pat, i)
  if j<0: break
  print(s3[max(0,j-60):j+120])
  print('---')
  i=j+len(pat)
  if i>50000: break
