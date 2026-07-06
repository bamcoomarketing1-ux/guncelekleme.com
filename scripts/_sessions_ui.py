import pathlib
t = pathlib.Path('public/assets/index-ChvzUPTI.js').read_text(encoding='utf-8')
idx = t.find('Oturumlar al')
print(t[max(0,idx-400):idx+2000].encode('ascii','replace').decode())
