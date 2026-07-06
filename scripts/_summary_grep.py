from pathlib import Path
s=Path(r'C:\Users\Developer\Desktop\sad\Yeni klasör\alisulasyon-laravel\public\assets\index-ChvzUPTI.js').read_text(encoding='utf-8')
i=s.find('c.value')
while i>=0 and i < 500000:
 chunk=s[i:i+80]
 if 'summary' in chunk or 'c.value.' in chunk:
  print(s[i-20:i+120])
 i=s.find('c.value.', i+1)
# also search summary.
for pat in ['summary.','c.value,']:
 idx=0
 while True:
  i=s.find(pat, idx)
  if i<0 or i>800000: break
  if 'history' in s[max(0,i-500):i] or 'VO' in s[max(0,i-200):i]:
   print('---', s[i:i+80])
  idx=i+1
