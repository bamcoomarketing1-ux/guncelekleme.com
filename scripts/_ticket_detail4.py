from pathlib import Path
s=Path(r'C:\Users\Developer\Desktop\sad\Yeni klasör\alisulasyon-laravel\public\assets\TicketDetailView-uTdU-kR9.js').read_text(encoding='utf-8')
for pat in ['participation', 'has_sponsor', 'tickets_count', 'status===', 'request', 'note', 'açıklama']:
 i=0
 while True:
  j=s.find(pat, i)
  if j<0: break
  print(s[max(0,j-100):j+180])
  print('---')
  i=j+len(pat)
  if i>100000: break

# notifications store in index
s2=Path(r'C:\Users\Developer\Desktop\sad\Yeni klasör\alisulasyon-laravel\public\assets\index-ChvzUPTI.js').read_text(encoding='utf-8')
for pat in ['fetchNotifications', 'announcement', 'notifications:']:
 i=s2.find(pat)
 if i>=0:
  print('NOTIF:', s2[i:i+400])
  print('===')
