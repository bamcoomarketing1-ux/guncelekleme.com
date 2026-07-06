from pathlib import Path
s=Path(r'C:\Users\Developer\Desktop\sad\Yeni klasör\alisulasyon-laravel\public\assets\SpecialOddsView-BKhmsouc.js').read_text(encoding='utf-8')
i=s.find('w-32 h-32')
print(s[i-300:i+400])
