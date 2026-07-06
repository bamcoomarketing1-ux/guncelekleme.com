from pathlib import Path
s=Path(r'C:\Users\Developer\Desktop\sad\Yeni klasör\alisulasyon-laravel\public\assets\SpecialOddsView-BKhmsouc.js').read_text(encoding='utf-8')
for pat in ['VER', 'AKT', 'src:', 'b,', 'img']:
 i=s.find(pat)
 if i>=0:
  print(s[i-30:i+200])
  print('---')
