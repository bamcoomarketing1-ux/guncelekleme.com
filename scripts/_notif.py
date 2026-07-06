from pathlib import Path
s=Path(r'C:\Users\Developer\Desktop\sad\Yeni klasör\alisulasyon-laravel\public\assets\AccountNotifications-BrZRm2sg.js').read_text(encoding='utf-8')
# full component setup
idx=s.find('setup(')
print(s[idx:idx+1200])

print('\n=== store usage ===')
for pat in ['t(s).', 'notifications', 'announcement']:
 i=0
 while True:
  j=s.find(pat, i)
  if j<0: break
  print(s[max(0,j-40):j+100])
  i=j+len(pat)
  if i>5000: break
