import pathlib
t = pathlib.Path('public/assets/index-ChvzUPTI.js').read_text(encoding='utf-8')
idx = t.find('trial-bonuses')
while idx >= 0 and idx < len(t):
    chunk = t[max(0,idx-100):idx+400]
    if 'FormData' in chunk or 'append(' in chunk or 'image' in chunk.lower():
        print(chunk.encode('ascii','replace').decode()[:500])
        print('---')
    idx = t.find('trial-bonuses', idx+1)
    if idx > 500000: break
