import pathlib,re
t = pathlib.Path('public/assets/index-ChvzUPTI.js').read_text(encoding='utf-8')
for pat in ['support/messages','support/guest','\.reply','bot_reply','botReply']:
    idx = 0
    n = 0
    while n < 8:
        idx = t.find(pat if not pat.startswith('\\') else 'reply', idx)
        if pat == '\\.reply':
            for m in re.finditer(r'.{0,80}reply.{0,200}', t):
                chunk = m.group()
                if 'support' in chunk.lower() or 'bot' in chunk.lower() or 'mesaj' in chunk.lower():
                    print(chunk[:350].encode('ascii','replace').decode())
                    print('---')
            break
        if idx < 0: break
        if 'support' in t[max(0,idx-100):idx+300].lower() or pat == 'support/messages':
            print(t[max(0,idx-150):idx+400].encode('ascii','replace').decode())
            print('---')
        idx += 1
        n += 1
