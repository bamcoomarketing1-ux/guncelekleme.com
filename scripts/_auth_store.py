import pathlib
t = pathlib.Path('public/assets/index-ChvzUPTI.js').read_text(encoding='utf-8')
idx = t.find('cr("auth"')
print(t[idx:idx+1500].encode('ascii','replace').decode())
