import pathlib, re
t = pathlib.Path('public/assets/RaffleManagement-2pmP3qew.js').read_text(encoding='utf-8')
for m in re.finditer(r'/admin/[^"\']+', t):
    print(m.group())
print('---fields---')
for pat in ['total_prize','reward_type','winner_count','rules','start_date','ends_at','title','image']:
    i = 0
    while True:
        i = t.find(pat, i)
        if i < 0: break
        print(pat, ':', t[max(0,i-30):i+60].encode('ascii','replace').decode())
        i += len(pat)
        break
