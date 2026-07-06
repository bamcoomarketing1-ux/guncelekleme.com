from pathlib import Path
s=Path(r'C:\Users\Developer\Desktop\sad\Yeni klasör\alisulasyon-laravel\public\assets\TicketDetailView-uTdU-kR9.js').read_text(encoding='utf-8')
i=s.find('has_sponsor_linked')
print(s[i-200:i+1500])
