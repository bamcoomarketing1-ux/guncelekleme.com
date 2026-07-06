import pathlib
t = pathlib.Path('public/assets/RaffleDetailView-Dwknm6-t.js').read_text(encoding='utf-8')
idx = t.find('status==="ended"&&u.value')
print(t[idx:idx+2500].encode('ascii','replace').decode())
