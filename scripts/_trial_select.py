import pathlib
t = pathlib.Path('public/assets/index-ChvzUPTI.js').read_text(encoding='utf-8')
idx = t.find('vse=["value"]')
print(t[idx-200:idx+800].encode('ascii','replace').decode())
