import pathlib
t = pathlib.Path('public/assets/index-ChvzUPTI.js').read_text(encoding='utf-8')
idx = t.find('async function d(){if(!o())')
print(t[idx:idx+3500].encode('ascii','replace').decode())
