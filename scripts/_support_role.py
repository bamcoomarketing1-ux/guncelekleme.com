import pathlib,re
t = pathlib.Path('public/assets/index-ChvzUPTI.js').read_text(encoding='utf-8')
for m in re.finditer(r'.{0,50}\.role.{0,120}', t):
    chunk = m.group()
    if 'user' in chunk or 'bot' in chunk or 'assistant' in chunk:
        if 'support' in t[max(0,m.start()-500):m.end()+200].lower() or 'mesaj' in chunk.lower() or 'Canl' in t[max(0,m.start()-200):m.start()]:
            print(chunk[:200].encode('ascii','replace').decode())
            print('---')
