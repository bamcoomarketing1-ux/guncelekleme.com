import pathlib
t = pathlib.Path('public/assets/index-ChvzUPTI.js').read_text(encoding='utf-8')
idx = t.find('__name:"TrialBonusManagement"')
# find render part - look for "gse" after setup
idx2 = t.find('i("div",gse', idx)
if idx2 < 0:
    idx2 = t.find(',gse,[', idx)
print('found at', idx2)
print(t[idx2:idx2+1500].encode('ascii','replace').decode())
