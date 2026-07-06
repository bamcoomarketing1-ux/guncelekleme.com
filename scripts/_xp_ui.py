import pathlib
t = pathlib.Path('public/assets/index-ChvzUPTI.js').read_text(encoding='utf-8')
for term in ['xp_progress', 'next_level_xp', 'Tamamland']:
    idx = 0
    n = 0
    while n < 5:
        idx = t.find(term, idx)
        if idx < 0: break
        print(term, ':', t[max(0,idx-120):idx+180].encode('ascii','replace').decode())
        print('---')
        idx += len(term)
        n += 1
