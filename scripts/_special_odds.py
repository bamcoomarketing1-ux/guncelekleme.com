from pathlib import Path
s=Path(r'C:\Users\Developer\Desktop\sad\Yeni klasör\alisulasyon-laravel\public\assets\SpecialOddsView-BKhmsouc.js').read_text(encoding='utf-8')
idx=s.find('J={key:0')
print(s[idx:idx+800])
# find b usage
for pat in ['b,', 'src:b', 'AKTIF', 'assets/']:
 i=s.find(pat)
 if i>=0:
  print('---', pat, s[i-50:i+150])
