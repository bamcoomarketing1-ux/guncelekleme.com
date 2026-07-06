from pathlib import Path
s=Path(r'C:\Users\Developer\Desktop\sad\Yeni klasör\alisulasyon-laravel\public\assets\TicketDetailView-uTdU-kR9.js').read_text(encoding='utf-8')
# what shows when NOT has_sponsor_linked
i=s.find('has_sponsor_linked')
while i>=0:
 print(s[i:i+500])
 print('===')
 i=s.find('has_sponsor_linked', i+1)
 if i>80000: break

# purchase flow
for pat in ['ticket-participation', 'Bilet Sat', 'satın al', 'purchase', 'join']:
 i=s.find(pat)
 if i>=0:
  print('PURCHASE:', s[max(0,i-100):i+200])
