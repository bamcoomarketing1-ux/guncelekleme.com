import pathlib
t = pathlib.Path('public/assets/RaffleDetailView-Dwknm6-t.js').read_text(encoding='utf-8')
idx = t.find('u.value.slice')
print(t[idx:idx+2000].encode('ascii','replace').decode())
