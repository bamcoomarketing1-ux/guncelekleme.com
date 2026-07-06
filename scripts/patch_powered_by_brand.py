#!/usr/bin/env python3
"""Patch frontend bundle: Powered by Brand admin settings + dynamic footer."""
from __future__ import annotations

import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
TARGETS = [
    ROOT / 'public' / 'assets' / 'index-ChvzUPTI.js',
    ROOT / 'resources' / 'frontend' / 'assets' / 'index-ChvzUPTI.js',
]

REPLACEMENTS: list[tuple[str, str]] = [
    (
        'quick_access_style:"design1"}),t=N(!1)',
        'quick_access_style:"design1",powered_by_enabled:!0,powered_by_text:"Powered by",powered_by_link:"https://t.me/blonkdigital",powered_by_logo:""}),t=N(!1)',
    ),
    (
        'quick_access_style:l.quick_access_style||"design1"}',
        'quick_access_style:l.quick_access_style||"design1",powered_by_enabled:l.powered_by_enabled!==void 0?!!l.powered_by_enabled:!0,powered_by_text:l.powered_by_text||"Powered by",powered_by_link:l.powered_by_link||"https://t.me/blonkdigital",powered_by_logo:l.powered_by_logo||""}',
    ),
    (
        'xp_system_enabled:!0}),o=N({logo:null,favicon:null,opening_gif:null,opening_gif_mobile:null}),d=N({logo:null,favicon:null,opening_gif:null,opening_gif_mobile:null}),l=N({logo:!1,favicon:!1,opening_gif:!1,opening_gif_mobile:!1})',
        'xp_system_enabled:!0,powered_by_enabled:!0,powered_by_text:"Powered by",powered_by_link:"https://t.me/blonkdigital"}),o=N({logo:null,favicon:null,opening_gif:null,opening_gif_mobile:null,powered_by_logo:null}),d=N({logo:null,favicon:null,opening_gif:null,opening_gif_mobile:null,powered_by_logo:null}),l=N({logo:!1,favicon:!1,opening_gif:!1,opening_gif_mobile:!1,powered_by_logo:!1})',
    ),
    (
        'o.value.opening_gif_mobile=g.opening_gif_mobile,g.primary_color&&s.applyColors(g.primary_color)',
        'o.value.opening_gif_mobile=g.opening_gif_mobile,o.value.powered_by_logo=g.powered_by_logo,g.primary_color&&s.applyColors(g.primary_color)',
    ),
    (
        'l.value.opening_gif_mobile&&h.append("remove_opening_gif_mobile","1"),await ne.post("/settings",h',
        'l.value.opening_gif_mobile&&h.append("remove_opening_gif_mobile","1"),h.append("powered_by_enabled",a.value.powered_by_enabled?"1":"0"),h.append("powered_by_text",a.value.powered_by_text||"Powered by"),h.append("powered_by_link",a.value.powered_by_link||""),d.value.powered_by_logo&&h.append("powered_by_logo",d.value.powered_by_logo),l.value.powered_by_logo&&h.append("remove_powered_by_logo","1"),await ne.post("/settings",h',
    ),
]

FOOTER_SETUP_RE = re.compile(
    r'o=new Date\(\)\.getFullYear\(\),l=`\$\{[^`]+\}/storage/footer/footer\.svg`;return\(c,u\)=>'
)

FOOTER_ANCHOR_OLD = (
    'i("a",{href:"https://t.me/blonkdigital",target:"_blank",rel:"noopener noreferrer",class:"order-first md:order-last flex items-center gap-2"},'
    '[u[7]||(u[7]=i("span",{class:"text-[10px] text-white/50 font-medium uppercase tracking-widest"},"Powered by",-1)),'
    'i("img",{src:l,alt:"Brand",class:"h-5 w-auto object-contain",loading:"lazy"})])'
)

FOOTER_ANCHOR_NEW = (
    'v(t).settings.powered_by_enabled!==!1?(x(),$("a",{key:0,href:v(t).settings.powered_by_link||"https://t.me/blonkdigital",target:"_blank",rel:"noopener noreferrer",class:"order-first md:order-last flex items-center gap-2"},'
    '[i("span",{class:"text-[10px] text-white/50 font-medium uppercase tracking-widest"},R(v(t).settings.powered_by_text||"Powered by"),1),'
    'i("img",{src:v(t).settings.powered_by_logo||b,alt:"Brand",class:"h-5 w-auto object-contain",loading:"lazy"},null,8,["src"])])):G("",!0)'
)

ADMIN_UI_ANCHOR = (
    'onClick:g[17]||(g[17]=os(k=>m("opening_gif_mobile"),["stop"])),'
    'class:"absolute top-2 right-2 p-2 bg-red-500 rounded-lg text-white opacity-0 group-hover:opacity-100 transition-opacity z-20"},'
    '[C(v(Qt),{class:"w-4 h-4"})])):G("",!0)])])])]),i("section",r6,['
)

ADMIN_UI_BLOCK = (
    'onClick:g[17]||(g[17]=os(k=>m("opening_gif_mobile"),["stop"])),'
    'class:"absolute top-2 right-2 p-2 bg-red-500 rounded-lg text-white opacity-0 group-hover:opacity-100 transition-opacity z-20"},'
    '[C(v(Qt),{class:"w-4 h-4"})])):G("",!0)])]),'
    'i("div",{class:"md:col-span-2 bg-white/5 border border-white/10 rounded-3xl p-6 space-y-4"},['
    'i("div",{class:"flex items-center justify-between gap-4 px-2"},['
    'i("div",null,[i("span",{class:"text-[10px] font-black text-white/60 uppercase tracking-widest block"},"Powered by Brand"),'
    'i("p",{class:"text-[11px] text-white/35 font-medium mt-1"},"Footer altındaki marka görseli ve tıklama linki.")]),'
    'i("button",{type:"button",onClick:g[48]||(g[48]=k=>a.value.powered_by_enabled=!a.value.powered_by_enabled),'
    'class:W(["px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest transition-colors",a.value.powered_by_enabled?"bg-emerald-500/20 text-emerald-400 border border-emerald-500/30":"bg-white/5 text-white/40 border border-white/10"])},'
    'R(a.value.powered_by_enabled?"Açık":"Kapalı"),3)]),'
    'i("div",{class:"grid grid-cols-1 md:grid-cols-2 gap-4"},['
    'i("div",{class:"space-y-2"},[i("label",{class:"text-[10px] font-black text-white/50 uppercase tracking-widest ml-2"},"Metin"),'
    'ae(i("input",{"onUpdate:modelValue":g[49]||(g[49]=k=>a.value.powered_by_text=k),type:"text",class:"panel-input w-full px-4 py-3 font-bold",placeholder:"Powered by"},null,512),[[ue,a.value.powered_by_text]])]),'
    'i("div",{class:"space-y-2"},[i("label",{class:"text-[10px] font-black text-white/50 uppercase tracking-widest ml-2"},"Tıklama Linki"),'
    'ae(i("input",{"onUpdate:modelValue":g[50]||(g[50]=k=>a.value.powered_by_link=k),type:"url",class:"panel-input w-full px-4 py-3 font-bold",placeholder:"https://t.me/blonkdigital"},null,512),[[ue,a.value.powered_by_link]])])]),'
    'i("div",{class:"relative group aspect-[5/1] bg-black/40 rounded-2xl border border-white/10 overflow-hidden flex items-center justify-center"},['
    'o.value.powered_by_logo?(x(),$("img",{key:0,src:o.value.powered_by_logo,class:"max-h-full max-w-full object-contain p-4",loading:"lazy"},null,8,["src"])):(x(),$("div",{key:1,class:"text-[10px] font-black text-white/30 uppercase"},"Brand Görseli Yok")),'
    'i("input",{type:"file",onChange:g[51]||(g[51]=k=>p(k,"powered_by_logo")),class:"absolute inset-0 opacity-0 cursor-pointer z-10",accept:"image/*"},null,32),'
    'o.value.powered_by_logo?(x(),$("button",{key:2,type:"button",onClick:g[52]||(g[52]=os(k=>m("powered_by_logo"),["stop"])),class:"absolute top-2 right-2 p-2 bg-red-500 rounded-lg text-white opacity-0 group-hover:opacity-100 transition-opacity z-20"},[C(v(Qt),{class:"w-4 h-4"})])):G("",!0)])])])]),i("section",r6,['
)


def patch_footer_setup(text: str) -> str:
    if 'v(t).settings.powered_by_link' in text:
        return text

    def repl(match: re.Match[str]) -> str:
        return match.group(0).replace(',l=`', ';const b=`', 1)

    updated, count = FOOTER_SETUP_RE.subn(repl, text, count=1)
    if count == 0:
        raise SystemExit('Footer setup pattern not found')
    return updated


def patch_file(path: Path) -> None:
    text = path.read_text(encoding='utf-8')
    original = text

    for old, new in REPLACEMENTS:
        if old in text:
            text = text.replace(old, new, 1)

    text = patch_footer_setup(text)

    if FOOTER_ANCHOR_OLD in text:
        text = text.replace(FOOTER_ANCHOR_OLD, FOOTER_ANCHOR_NEW, 1)
    elif 'v(t).settings.powered_by_link' not in text:
        raise SystemExit(f'Footer anchor not found in {path.name}')

    if ADMIN_UI_ANCHOR in text:
        text = text.replace(ADMIN_UI_ANCHOR, ADMIN_UI_BLOCK, 1)

    if 'Powered by Brand' not in text:
        raise SystemExit(f'Admin UI block missing after patch: {path.name}')

    if text == original:
        print(f'Already up to date: {path}')
        return

    path.write_text(text, encoding='utf-8')
    print(f'Patched {path}')


def main() -> int:
    for target in TARGETS:
        if not target.is_file():
            print(f'Skip missing {target}', file=sys.stderr)
            continue
        patch_file(target)
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
