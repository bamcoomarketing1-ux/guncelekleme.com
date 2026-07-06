import pathlib
t = pathlib.Path('public/assets/index-ChvzUPTI.js').read_text(encoding='utf-8')
idx = t.find('TrialBonusManagement')
# find sponsor_id watch or change
chunk = t[idx:idx+8000]
for pat in ['sponsor_id', 'watch(', 'n.value', 'logo_full']:
    i = chunk.find(pat)
    if i >= 0:
        print(pat, chunk[max(0,i-50):i+150].encode('ascii','replace').decode())
        print('---')
