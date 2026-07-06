import pathlib
t = pathlib.Path('public/assets/AccountTickets-CBv855Pq.js').read_text(encoding='utf-8')
idx = t.find('participation-history')
print(t[max(0,idx-200):idx+1500].encode('ascii','replace').decode())
