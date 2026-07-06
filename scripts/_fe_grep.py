from pathlib import Path
s=Path(r'C:\Users\Developer\Desktop\sad\Yeni klasör\alisulasyon-laravel\public\assets\index-ChvzUPTI.js').read_text(encoding='utf-8')
for pat in ['bot_message','support/messages','gorilla','AKTIF MAC YOK','bets_count','is_participated','banners.tv','background']:
 idx=0
 c=0
 while c<3:
  i=s.find(pat, idx)
  if i<0: break
  print('---', pat, '---')
  print(s[max(0,i-100):i+200])
  idx=i+len(pat)
  c+=1
