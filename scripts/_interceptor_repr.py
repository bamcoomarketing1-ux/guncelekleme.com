import pathlib
t = pathlib.Path('public/assets/index-ChvzUPTI.js').read_text(encoding='utf-8')
idx = t.find('ne.interceptors.response.use')
print(repr(t[idx:idx+250]))
