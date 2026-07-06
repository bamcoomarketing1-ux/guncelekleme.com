import pathlib
t = pathlib.Path('public/assets/index-ChvzUPTI.js').read_text(encoding='utf-8')
idx = 0
while True:
    idx = t.find('fetchUser', idx)
    if idx < 0: break
    print(t[max(0,idx-60):idx+80].encode('ascii','replace').decode())
    print('---')
    idx += 1
    if idx > 2000000: break
