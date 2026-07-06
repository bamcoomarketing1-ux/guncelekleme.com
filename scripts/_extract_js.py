from pathlib import Path
import re

def extract(path, patterns):
    s=Path(path).read_text(encoding='utf-8')
    for pat in patterns:
        for m in re.finditer(pat, s):
            print(f'=== {pat} ===')
            print(s[max(0,m.start()-120):m.end()+200])
            print()

extract(r'C:\Users\Developer\Desktop\sad\Yeni klasör\alisulasyon-laravel\public\assets\MusicManagement-d-RSM6WN.js', 
        [r'youtube_url[^,]{0,200}', r'\.match\([^)]+\)', r'flatMap[^;]{0,300}'])
extract(r'C:\Users\Developer\Desktop\sad\Yeni klasör\alisulasyon-laravel\public\assets\PopupManagement-Dyf5f_ox.js',
        [r'unshift[^;]{0,200}', r'popups/\$\{[^}]+\}', r'\.data\s*\?\?', r'popup\.id'])
extract(r'C:\Users\Developer\Desktop\sad\Yeni klasör\alisulasyon-laravel\public\assets\RaffleManagement-2pmP3qew.js',
        [r'participants[^;]{0,400}', r'user\.username', r'\.username'])
