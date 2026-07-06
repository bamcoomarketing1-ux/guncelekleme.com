import pathlib,re
t = pathlib.Path('public/assets/index-ChvzUPTI.js').read_text(encoding='utf-8')
for m in re.finditer(r'.{0,100}/admin/users/.{0,200}', t):
    s = m.group()
    if 'xp' in s.lower() or 'balance' in s.lower():
        print(s.encode('ascii','replace').decode())
        print('---')
