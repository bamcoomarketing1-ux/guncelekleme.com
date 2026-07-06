import pathlib
t = pathlib.Path('public/assets/index-ChvzUPTI.js').read_text(encoding='utf-8')
idx = t.find('/api')
while idx >= 0 and idx < len(t):
    chunk = t[max(0,idx-200):idx+400]
    if 'baseURL' in chunk or 'interceptors' in chunk or 'success' in chunk:
        print(chunk.encode('ascii','replace').decode())
        print('====')
    idx = t.find('/api', idx+1)
    if idx > 250000: break
