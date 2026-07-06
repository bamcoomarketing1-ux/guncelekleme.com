import pathlib,re
t = pathlib.Path('public/assets/index-ChvzUPTI.js').read_text(encoding='utf-8')
# find axios create or interceptors response
for pat in ['transformResponse', 'interceptors.response', 'status==="success"', 'success:e.status']:
    idx = t.find(pat)
    print(pat, idx)
    if idx >= 0:
        print(t[max(0,idx-100):idx+400].encode('ascii','replace').decode()[:500])
        print('---')
