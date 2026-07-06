#!/usr/bin/env python3
"""Footer admin settings + null-safe team logos in frontend bundle."""
from __future__ import annotations

import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
TARGETS = [
    ROOT / 'public' / 'assets' / 'index-ChvzUPTI.js',
    ROOT / 'resources' / 'frontend' / 'assets' / 'index-ChvzUPTI.js',
]

REPLACEMENTS: list[tuple[str, str]] = [
    (
        'powered_by_logo:""}),t=N(!1)',
        'powered_by_logo:"",footer_tagline:"Topluluğumuza katıl, kazanmaya başla.",footer_show_social:!0}),t=N(!1)',
    ),
    (
        'powered_by_logo:l.powered_by_logo||""}',
        'powered_by_logo:l.powered_by_logo||"",footer_tagline:l.footer_tagline||"Topluluğumuza katıl, kazanmaya başla.",footer_show_social:l.footer_show_social!==void 0?!!l.footer_show_social:!0}',
    ),
    (
        'powered_by_link:"https://t.me/blonkdigital"}),o=N({logo:null',
        'powered_by_link:"https://t.me/blonkdigital",footer_tagline:"Topluluğumuza katıl, kazanmaya başla.",footer_show_social:!0}),o=N({logo:null',
    ),
    (
        'l.value.powered_by_logo&&h.append("remove_powered_by_logo","1"),await ne.post("/settings",h',
        'l.value.powered_by_logo&&h.append("remove_powered_by_logo","1"),h.append("footer_tagline",a.value.footer_tagline||""),h.append("footer_show_social",a.value.footer_show_social?"1":"0"),await ne.post("/settings",h',
    ),
    (
        's.value.length>0?(x(),$("div",OP,',
        'v(t).settings.footer_show_social!==!1&&s.value.length>0?(x(),$("div",OP,',
    ),
    (
        'u[4]||(u[4]=i("p",{class:"text-[11px] text-white/50 font-medium leading-relaxed max-w-[200px]"}," Topluluğumuza katıl, kazanmaya başla. ",-1))',
        'i("p",{class:"text-[11px] text-white/50 font-medium leading-relaxed max-w-[200px]"},R(v(t).settings.footer_tagline||"Topluluğumuza katıl, kazanmaya başla."),1)',
    ),
    (
        'src:oe.home_team.logo_url',
        'src:oe.home_team?.logo_url||""',
    ),
    (
        'R(oe.home_team.name)',
        'R(oe.home_team?.name||"—")',
    ),
    (
        'src:oe.away_team.logo_url',
        'src:oe.away_team?.logo_url||""',
    ),
    (
        'R(oe.away_team.name)',
        'R(oe.away_team?.name||"—")',
    ),
    (
        'src:b.sponsor.logo_url',
        'src:b.sponsor?.logo_url||""',
    ),
    (
        'src:V.sponsor.logo_url',
        'src:V.sponsor?.logo_url||""',
    ),
]

FOOTER_ADMIN_ANCHOR = (
    'i("div",{class:"md:col-span-2 bg-white/5 border border-white/10 rounded-3xl p-6 space-y-4"},['
    'i("div",{class:"flex items-center justify-between gap-4 px-2"},['
    'i("div",null,[i("span",{class:"text-[10px] font-black text-white/60 uppercase tracking-widest block"},"Powered by Brand")'
)

FOOTER_ADMIN_BLOCK = (
    'i("div",{class:"md:col-span-2 bg-white/5 border border-white/10 rounded-3xl p-6 space-y-5"},['
    'i("div",null,[i("span",{class:"text-[10px] font-black text-white/60 uppercase tracking-widest block"},"Footer Ayarları"),'
    'i("p",{class:"text-[11px] text-white/35 font-medium mt-1"},"Alt bilgi metni, sosyal medya ikonları ve marka alanı.")]),'
    'i("div",{class:"space-y-2"},[i("label",{class:"text-[10px] font-black text-white/50 uppercase tracking-widest ml-2"},"Footer Açıklama Metni"),'
    'ae(i("textarea",{"onUpdate:modelValue":g[53]||(g[53]=k=>a.value.footer_tagline=k),rows:"2",class:"panel-input w-full px-4 py-3 font-medium resize-none",placeholder:"Topluluğumuza katıl, kazanmaya başla."},null,512),[[ue,a.value.footer_tagline]])]),'
    'i("div",{class:"flex items-center justify-between gap-4 px-2 py-3 rounded-2xl bg-black/20 border border-white/10"},['
    'i("div",null,[i("span",{class:"text-[10px] font-black text-white/60 uppercase tracking-widest block"},"Sosyal Medya İkonları"),'
    'i("p",{class:"text-[11px] text-white/35 font-medium mt-1"},"Linkler: Panel → Sosyal Medya menüsünden yönetilir.")]),'
    'i("button",{type:"button",onClick:g[54]||(g[54]=k=>a.value.footer_show_social=!a.value.footer_show_social),'
    'class:W(["px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest transition-colors",a.value.footer_show_social?"bg-emerald-500/20 text-emerald-400 border border-emerald-500/30":"bg-white/5 text-white/40 border border-white/10"])},'
    'R(a.value.footer_show_social?"Göster":"Gizle"),3)])]),'
    'i("div",{class:"md:col-span-2 bg-white/5 border border-white/10 rounded-3xl p-6 space-y-4"},['
    'i("div",{class:"flex items-center justify-between gap-4 px-2"},['
    'i("div",null,[i("span",{class:"text-[10px] font-black text-white/60 uppercase tracking-widest block"},"Powered by Brand")'
)


def patch_file(path: Path) -> None:
    text = path.read_text(encoding='utf-8')
    original = text

    for old, new in REPLACEMENTS:
        if old not in text:
            if old.startswith('powered_by_logo:""}') and 'footer_tagline' in text:
                continue
            raise SystemExit(f'Missing pattern in {path.name}: {old[:90]}...')
        text = text.replace(old, new, 1)

    if FOOTER_ADMIN_ANCHOR in text:
        text = text.replace(FOOTER_ADMIN_ANCHOR, FOOTER_ADMIN_BLOCK, 1)
    elif 'Footer Ayarları' not in text:
        raise SystemExit(f'Footer admin anchor missing in {path.name}')

    if text == original:
        print(f'Already up to date: {path}')
        return

    path.write_text(text, encoding='utf-8')
    print(f'Patched {path}')


def main() -> int:
    for target in TARGETS:
        if target.is_file():
            patch_file(target)
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
