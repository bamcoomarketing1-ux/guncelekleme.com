import pathlib
t = pathlib.Path('public/assets/RafflesView-C60kthx0.js').read_text(encoding='utf-8')
for pat in ['/raffles', 'time_left', 'progress', 'upcoming']:
    i = t.find(pat)
    if i >= 0:
        print(pat, t[i:i+150].encode('ascii','replace').decode())
