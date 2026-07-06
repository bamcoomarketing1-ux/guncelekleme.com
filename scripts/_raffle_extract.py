import re, pathlib
t = pathlib.Path(r'public/assets/RaffleDetailView-Dwknm6-t.js').read_text(encoding='utf-8')
for m in re.finditer(r'/raffles[^`"\']*', t):
    print(m.group())
i = t.find('t.value.')
count = 0
while count < 25:
    i = t.find('t.value.', i+1)
    if i < 0: break
    print(t[i:i+80].encode('ascii','replace').decode())
    count += 1
