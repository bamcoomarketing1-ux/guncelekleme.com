from pathlib import Path
import re
s=Path(r'C:\Users\Developer\Desktop\sad\Yeni klasör\alisulasyon-laravel\public\assets\TournamentBracket-C_oVp4vx.js').read_text(encoding='utf-8')
for m in re.findall(r'/admin/tournaments[^`"\']*', s):
    print(m)
