from pathlib import Path
import re

files = [
    'TicketDetailView-uTdU-kR9.js',
    'TicketsView-BaGiBzgx.js',
    'MarketManagement-BLGnBLI7.js',
    'AccountNotifications-BrZRm2sg.js',
]
base = Path(r'C:\Users\Developer\Desktop\sad\Yeni klasör\alisulasyon-laravel\public\assets')

for fname in files:
    s = (base / fname).read_text(encoding='utf-8')
    print('====', fname, '====')
    for pat in ['/ticket', 'Talep', 'description', 'açıklama', 'ticket-requests', 'notifications', 'image_path', 'image_url', 'post(']:
        for m in re.finditer(re.escape(pat) if pat.startswith('/') else pat, s):
            print(s[max(0,m.start()-80):m.end()+150])
            print('---')
            break
