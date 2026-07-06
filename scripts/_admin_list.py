import pathlib
t = pathlib.Path('public/assets/index-ChvzUPTI.js').read_text(encoding='utf-8')
idx = t.find('s.value=L.data')
print(t[max(0,idx-100):idx+800].encode('ascii','replace').decode())
