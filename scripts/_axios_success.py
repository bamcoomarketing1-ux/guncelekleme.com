import pathlib,re
t = pathlib.Path('public/assets/index-ChvzUPTI.js').read_text(encoding='utf-8')
for pat in ['interceptors.response.use', 'return e.data', 'response.data', '.success']:
    idx = t.find('interceptors.response.use')
    if idx >= 0:
        print(t[idx:idx+500].encode('ascii','replace').decode())
        break

# admin balance update for comparison
idx = t.find('/balance`,A.value)')
print('---balance---')
print(t[idx-100:idx+300].encode('ascii','replace').decode())
