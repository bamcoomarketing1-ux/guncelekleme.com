import pathlib
t = pathlib.Path('public/assets/index-ChvzUPTI.js').read_text(encoding='utf-8')
idx = t.find('BonusManagement')
print(t[idx:idx+2000].encode('ascii','replace').decode())
