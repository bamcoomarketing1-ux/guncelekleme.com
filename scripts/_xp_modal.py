import pathlib
t = pathlib.Path('public/assets/index-ChvzUPTI.js').read_text(encoding='utf-8')
idx = t.find('M.value={xp:')
if idx < 0:
    idx = t.find('M.value=')
    # find near xp
    while idx >= 0:
        chunk = t[idx:idx+200]
        if 'xp' in chunk:
            print(chunk.encode('ascii','replace').decode())
            break
        idx = t.find('M.value=', idx+1)
else:
    print(t[max(0,idx-200):idx+300].encode('ascii','replace').decode())
