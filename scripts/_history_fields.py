from pathlib import Path
s=Path(r'C:\Users\Developer\Desktop\sad\Yeni klasör\alisulasyon-laravel\public\assets\index-ChvzUPTI.js').read_text(encoding='utf-8')
# history row fields
i=s.find('y.profit')
if i>=0:
 print('profit', s[i-200:i+400])
for pat in ['y.detail','y.multiplier','y.status','y.product_name','y.ticket_number','summary.total','summary.profit','c.value.']:
 idx=s.find(pat)
 if idx>=0:
  print('---', pat)
  print(s[idx-100:idx+200])
