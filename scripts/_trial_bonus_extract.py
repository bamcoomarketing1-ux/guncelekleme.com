import pathlib, re
t = pathlib.Path('public/assets/index-ChvzUPTI.js').read_text(encoding='utf-8')
# find trial-bonus admin section
for pat in ['trial-bonus', 'trial_bonus', 'sponsor_id', 'amount', 'currency', 'toString']:
    idx = 0
    count = 0
    while count < 3:
        idx = t.find(pat, idx)
        if idx < 0: break
        print('===', pat, 'at', idx, '===')
        print(t[max(0,idx-100):idx+200].encode('ascii','replace').decode())
        idx += len(pat)
        count += 1
