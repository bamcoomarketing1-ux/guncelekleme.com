from pathlib import Path
s=Path(r'C:\Users\Developer\Desktop\sad\Yeni klasör\alisulasyon-laravel\public\assets\SpecialOddsView-BKhmsouc.js').read_text(encoding='utf-8')
# find render empty
idx=s.find(':key:0')
while idx>=0:
 chunk=s[idx:idx+500]
 if 'J' in chunk or 'py-40' in chunk or 'length' in chunk:
  print(chunk)
  print('===')
 idx=s.find(':key:0', idx+1)
