import pathlib
t = pathlib.Path('public/assets/index-ChvzUPTI.js').read_text(encoding='utf-8')
idx = t.find('Deneme Bonuslar')
# search backwards for modal with sponsor select
start = t.find('Lte', idx-5000)
print(t[start:start+6000].encode('ascii','replace').decode())
