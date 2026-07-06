import pathlib, re
t = pathlib.Path('public/assets/RaffleDetailView-Dwknm6-t.js').read_text(encoding='utf-8')
# winners loop
for pat in ['u.value', 'winners', 'is_participated', 'R.get', 'R.post']:
    print('===', pat, '===')
    for m in re.finditer(re.escape(pat), t):
        print(t[m.start():m.start()+120].encode('ascii','replace').decode())
        break
