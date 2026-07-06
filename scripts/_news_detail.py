from pathlib import Path
s=Path(r'C:\Users\Developer\Desktop\sad\Yeni klasör\alisulasyon-laravel\public\assets\NewsDetailView-m8pjF7Mq.js').read_text(encoding='utf-8')
for pat in ['/news', 'slug', 'get(']:
 i=s.find(pat)
 if i>=0:
  print(s[i-50:i+150])
  print('---')
