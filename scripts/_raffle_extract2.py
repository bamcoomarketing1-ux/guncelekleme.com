import pathlib
for fname in ['RafflesView-C60kthx0.js', 'RaffleDetailView-Dwknm6-t.js']:
    t = pathlib.Path('public/assets', fname).read_text(encoding='utf-8')
    print('===', fname, '===')
    for pat in ['total_prize', 'reward_type', 'status', 'time_left', 'start_date', 'winner_count', 'rules', 'winners']:
        if pat in t:
            i = t.find(pat)
            print(pat, t[i:i+100].encode('ascii','replace').decode())
    print()
