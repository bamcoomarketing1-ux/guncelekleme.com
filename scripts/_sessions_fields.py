import pathlib
t = pathlib.Path('public/assets/index-ChvzUPTI.js').read_text(encoding='utf-8')
idx = t.find('c.is_current')
print(t[idx:idx+2500].encode('ascii','replace').decode())
