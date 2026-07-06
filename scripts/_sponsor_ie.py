from pathlib import Path
s=Path(r'C:\Users\Developer\Desktop\sad\Yeni klasör\alisulasyon-laravel\public\assets\TicketDetailView-uTdU-kR9.js').read_text(encoding='utf-8')
i=s.find(':(s(),a("div",Ie')
print(s[i:i+800])
