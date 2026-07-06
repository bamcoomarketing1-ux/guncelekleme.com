import pathlib
t = pathlib.Path('public/assets/index-ChvzUPTI.js').read_text(encoding='utf-8')
# search success:true pattern
for pat in ['success:!0', 'success:true', 'success:e.', 'success:t.', 'success:n.', 'success:r.', 'success:a.', 'success:o.', 'success:d.', 'success:l.', 'success:c.', 'success:u.']:
    idx = 0
    count = 0
    while count < 3:
        idx = t.find(pat, idx)
        if idx < 0: break
        print(pat, t[max(0,idx-150):idx+200].encode('ascii','replace').decode())
        print('---')
        idx += 1
        count += 1
