import pathlib, re
t = pathlib.Path('public/assets/index-ChvzUPTI.js').read_text(encoding='utf-8')
for pat in ['interceptors.response', 'e.data', 'return e.data', 'baseURL']:
    i = t.find(pat)
    if i >= 0:
        print(pat, t[max(0,i-80):i+200].encode('ascii','replace').decode())
        print('---')
