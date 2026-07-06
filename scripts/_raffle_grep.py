from pathlib import Path
s=Path(r'C:\Users\Developer\Desktop\sad\Yeni klasör\alisulasyon-laravel\public\assets\RaffleManagement-2pmP3qew.js').read_text(encoding='utf-8')
idx=s.find('participant')
while idx>=0 and idx<len(s):
 print(s[max(0,idx-80):idx+120])
 print('---')
 idx=s.find('participant', idx+1)
 if idx>50000: break
