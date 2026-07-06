import pathlib
t = pathlib.Path('public/assets/index-ChvzUPTI.js').read_text(encoding='utf-8')
idx = t.find('const ne=')
if idx < 0:
    idx = t.find('ne=cr(')
print('idx', idx)
print(t[idx:idx+2500].encode('ascii','replace').decode())
