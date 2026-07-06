from pathlib import Path
s=Path(r'C:\Users\Developer\Desktop\sad\Yeni klasör\alisulasyon-laravel\public\assets\index-ChvzUPTI.js').read_text(encoding='utf-8')
idx=s.find('Tümü Geçmişi')
print(s[idx-1200:idx+800])
