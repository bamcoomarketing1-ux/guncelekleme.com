import pathlib
for f in ['BonusesView-D8QPhmst.js','TrialBonusesView-BgJai0KZ.js']:
    t = pathlib.Path('public/assets/'+f).read_text(encoding='utf-8')
    idx = t.find('Bonusu Al')
    print(f, t[max(0,idx-150):idx+120].encode('ascii','replace').decode())
