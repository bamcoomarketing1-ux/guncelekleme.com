from pathlib import Path
s=Path(r'C:\Users\Developer\Desktop\sad\Yeni klasör\alisulasyon-laravel\public\assets\TournamentManagement-DQCiRZae.js').read_text(encoding='utf-8')
i=s.find('j=async')
print(s[i:i+600])
