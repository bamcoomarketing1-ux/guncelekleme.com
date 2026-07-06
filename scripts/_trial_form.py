import pathlib
t = pathlib.Path('public/assets/index-ChvzUPTI.js').read_text(encoding='utf-8')
idx = t.find('TrialBonusManagement')
chunk = t[idx:idx+12000]
# modal form sponsor select
for pat in ['@change', 'v-model', 'c.value.title', 'find(', 'n.value.find']:
    i = chunk.find(pat)
    if i >= 0:
        print(pat, chunk[i:i+250].encode('ascii','replace').decode())
        print('---')
