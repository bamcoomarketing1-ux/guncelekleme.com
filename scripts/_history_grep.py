from pathlib import Path
import re
s=Path(r'C:\Users\Developer\Desktop\sad\Yeni klasör\alisulasyon-laravel\public\assets\index-ChvzUPTI.js').read_text(encoding='utf-8')
for pat in ['bet_amount','win_amount','date_formatted','type_label','/history?','Hesap Geçmişi','summary']:
 idx=0
 while True:
  i=s.find(pat, idx)
  if i<0: break
  print('---', pat, '---')
  print(s[max(0,i-150):i+250])
  idx=i+len(pat)
  if idx>500000: break
