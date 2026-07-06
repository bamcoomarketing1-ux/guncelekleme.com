import pathlib
t = pathlib.Path('public/assets/index-ChvzUPTI.js').read_text(encoding='utf-8')
idx = t.find('xp_progress||0)+" Tamamland')
print(t[idx-400:idx+600].encode('ascii','replace').decode())
print('---')
idx2 = t.find('M.value')
# admin xp modal
idx3 = t.find('M=N({xp:0,level:1})')
print(t[idx3:idx3+800].encode('ascii','replace').decode())
