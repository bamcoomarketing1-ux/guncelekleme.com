import pathlib,re
t = pathlib.Path('public/assets/index-ChvzUPTI.js').read_text(encoding='utf-8')
for m in re.finditer(r'\.(success|status)\s*&&', t):
    start = max(0, m.start()-80)
    end = min(len(t), m.end()+80)
    chunk = t[start:end]
    if 'admin' in chunk.lower() or 'xp' in chunk.lower() or 'balance' in chunk.lower():
        print(chunk.encode('ascii','replace').decode())
        print('---')
