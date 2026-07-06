import pathlib
t = pathlib.Path('public/assets/index-ChvzUPTI.js').read_text(encoding='utf-8')
idx = t.find('/admin/bonuses')
print(t[idx-200:idx+1500].encode('ascii','replace').decode())
