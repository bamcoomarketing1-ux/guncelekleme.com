import pathlib
t = pathlib.Path('public/assets/index-ChvzUPTI.js').read_text(encoding='utf-8')
idx = t.find('Deneme bonuslar')
print(t[idx-500:idx+3500].encode('ascii','replace').decode())
