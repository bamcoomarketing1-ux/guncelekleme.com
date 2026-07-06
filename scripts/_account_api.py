import pathlib,re
for name in ['AccountTickets-CBv855Pq.js','AccountSponsors-DgcS_b2u.js','index-ChvzUPTI.js']:
    t = pathlib.Path('public/assets/'+name).read_text(encoding='utf-8')
    for pat in ['participation-history','user/sponsors','/sessions','account/sessions']:
        if pat in t:
            for m in re.finditer(re.escape(pat)+r'.{0,300}', t):
                print(name, pat, ':', m.group()[:350].encode('ascii','replace').decode())
                print('---')
