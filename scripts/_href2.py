import pathlib,re
for f in ['BonusesView-D8QPhmst.js','TrialBonusesView-BgJai0KZ.js']:
    t = pathlib.Path('public/assets/'+f).read_text(encoding='utf-8')
    for m in re.finditer(r'href:[^,}]+', t):
        print(f, m.group())
