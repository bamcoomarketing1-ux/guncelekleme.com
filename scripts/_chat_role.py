import pathlib
t = pathlib.Path('public/assets/index-ChvzUPTI.js').read_text(encoding='utf-8')
for pat in ['role==="user"', 'role==="bot"', 'role!=="user"', 'm.role']:
    idx = t.find(pat)
    if idx >= 0:
        print(pat, ':', t[max(0,idx-80):idx+200].encode('ascii','replace').decode())
        print('---')
