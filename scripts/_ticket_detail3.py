from pathlib import Path
s=Path(r'C:\Users\Developer\Desktop\sad\Yeni klasör\alisulasyon-laravel\public\assets\TicketDetailView-uTdU-kR9.js').read_text(encoding='utf-8')
# find purchase/buy
for pat in ['participation', 'satın', 'Bilet Al', 'purchase', 'buy', 'user_tickets', 'user_requests', 'sponsor_linked', 'l.value.']:
 idx=0
 c=0
 while c<8:
  i=s.find(pat, idx)
  if i<0: break
  print('---', pat, '---')
  print(s[max(0,i-80):i+200])
  idx=i+len(pat)
  c+=1

print('\n=== AccountNotifications fetch ===')
s2=Path(r'C:\Users\Developer\Desktop\sad\Yeni klasör\alisulasyon-laravel\public\assets\AccountNotifications-BrZRm2sg.js').read_text(encoding='utf-8')
i=s2.find('async')
print(s2[i:i+800])
