from pathlib import Path
s=Path(r'C:\Users\Developer\Desktop\sad\Yeni klasör\alisulasyon-laravel\public\assets\TournamentBracket-C_oVp4vx.js').read_text(encoding='utf-8')
i=s.find('get(`/admin/tournaments/')
print(s[i:i+800])
