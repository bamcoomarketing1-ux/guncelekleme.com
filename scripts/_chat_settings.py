import pathlib
t = pathlib.Path('public/assets/index-ChvzUPTI.js').read_text(encoding='utf-8')
for pat in ['chat_enabled','support_system_prompt','chat_bot_name','Chat Ayar']:
    idx = t.find(pat)
    if idx >= 0:
        print(t[max(0,idx-200):idx+400].encode('ascii','replace').decode())
        print('---')
