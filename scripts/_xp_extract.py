import pathlib,re
t = pathlib.Path('public/assets/index-ChvzUPTI.js').read_text(encoding='utf-8')
for pat in ['/users/', '/xp', 'progressFields', 'xp_to_next', 'xp_progress', 'current_xp', 'user.xp', 'user.value.xp', 'fetchUser', '/user"']:
    idx = 0
    count = 0
    while count < 2:
        idx = t.find(pat, idx)
        if idx < 0: break
        print('===', pat, '===')
        print(t[max(0,idx-80):idx+200].encode('ascii','replace').decode())
        idx += len(pat)
        count += 1
