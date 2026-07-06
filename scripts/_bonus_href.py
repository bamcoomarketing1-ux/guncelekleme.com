import pathlib
t = pathlib.Path('public/assets/BonusesView-D8QPhmst.js').read_text(encoding='utf-8')
idx = t.find(',fe=')
if idx < 0:
    idx = t.find('href:s.value')
print(t[max(0,idx-50):idx+200].encode('ascii','replace').decode())
