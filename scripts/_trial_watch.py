import pathlib
t = pathlib.Path('public/assets/index-ChvzUPTI.js').read_text(encoding='utf-8')
idx = t.find('c.value.sponsor_id')
while idx >= 0:
    print(t[max(0,idx-80):idx+200].encode('ascii','replace').decode())
    print('---')
    idx = t.find('c.value.sponsor_id', idx+1)
    if idx > 1056000: break
