import pathlib,re
t = pathlib.Path('public/assets/index-ChvzUPTI.js').read_text(encoding='utf-8')
# find new Function usage
for m in re.finditer(r'new Function', t):
    print('at', m.start(), t[m.start()-50:m.start()+80].encode('ascii','replace').decode())
    if m.start() > 500000: break
print('count', len(re.findall(r'new Function', t)))
