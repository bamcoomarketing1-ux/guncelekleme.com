import pathlib
t = pathlib.Path('public/assets/index-ChvzUPTI.js').read_text(encoding='utf-8')
idx = t.find('B.success&&(t.success(B.message)')
print(t[max(0,idx-500):idx+300].encode('ascii','replace').decode())
