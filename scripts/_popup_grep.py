from pathlib import Path
import re
s=Path(r'C:\Users\Developer\Desktop\sad\Yeni klasör\alisulasyon-laravel\public\assets\PopupManagement-Dyf5f_ox.js').read_text(encoding='utf-8')
for m in re.findall(r'.{0,80}(popups|\.id|data).{0,80}', s):
    if 'popup' in m.lower() or 'id' in m:
        print(m[:160])
        print('---')
