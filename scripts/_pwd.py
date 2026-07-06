import pathlib
t = pathlib.Path('public/assets/index-ChvzUPTI.js').read_text(encoding='utf-8')
idx = t.find('change-password')
print(t[max(0,idx-300):idx+500].encode('ascii','replace').decode())
