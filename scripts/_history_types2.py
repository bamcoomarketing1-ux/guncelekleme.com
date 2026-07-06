from pathlib import Path
s=Path(r'C:\Users\Developer\Desktop\sad\Yeni klasör\alisulasyon-laravel\public\assets\index-ChvzUPTI.js').read_text(encoding='utf-8')
for kw in ['Tümü','special_odds','mines','promocode','label:']:
 idx=s.find(kw)
 if idx>=0:
  print(s[idx-100:idx+200])
  print('---')
