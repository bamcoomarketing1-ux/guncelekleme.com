#!/usr/bin/env python3
"""Patch compiled frontend bundles for platform bug fixes."""

import base64
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
TARGETS = [
    ROOT / "public/assets",
    ROOT / "resources/frontend/assets",
]

PLACEHOLDER_PNG = base64.b64decode(
    "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=="
)


def patch_file(path: Path, replacements: list[tuple[str, str]]) -> bool:
    if not path.exists():
        return False
    text = path.read_text(encoding="utf-8")
    original = text
    for old, new in replacements:
        if old not in text:
            continue
        text = text.replace(old, new, 1)
    if text != original:
        path.write_text(text, encoding="utf-8")
        return True
    return False


def ensure_placeholder() -> None:
    target = ROOT / "public/placeholder.png"
    if not target.exists():
        target.write_bytes(PLACEHOLDER_PNG)
        print(f"Created {target}")


def build_statistics_patches(stats_text: str) -> list[tuple[str, str]]:
    patches: list[tuple[str, str]] = []

    setup_old = "s=Xt(!1),n=Xt(null),o=Xt(null),r=["
    setup_new = "s=Xt(!1),n=Xt(null),o=Xt(null),b=Xt([]),r=["
    if setup_old in stats_text:
        patches.append((setup_old, setup_new))

    fetch_old = 'd.status==="success"&&(n.value=d.data.summary,o.value=d.data.charts)'
    fetch_new = (
        'd.status==="success"&&(n.value=d.data.summary,o.value=d.data.charts,'
        'b.value=d.data.summary?.sponsor_clicks_breakdown||[])'
    )
    if fetch_old in stats_text:
        patches.append((fetch_old, fetch_new))

    anchor = "])]),M(\"div\",uh,["
    idx = stats_text.find(anchor)
    if idx >= 0:
        analytics_grid = (
            '])]),M("div",{class:"grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mt-4"},['
            'M("div",{class:"bg-[#111318] border border-white/5 rounded-2xl p-5"},['
            'M("p",{class:"text-[9px] font-black text-gray-600 uppercase tracking-widest"},"Online",-1),'
            'M("p",{class:"text-3xl font-black text-emerald-400 italic mt-2"},'
            'K((n.value?.online_now??0).toLocaleString("tr-TR")),1),'
            'M("p",{class:"text-[10px] text-gray-500 font-bold mt-1 uppercase tracking-widest"},"Anlik Kullanici",-1)]),'
            'M("div",{class:"bg-[#111318] border border-white/5 rounded-2xl p-5"},['
            'M("p",{class:"text-[9px] font-black text-gray-600 uppercase tracking-widest"},"Ziyaretci",-1),'
            'M("p",{class:"text-3xl font-black text-sky-400 italic mt-2"},'
            'K((n.value?.visitors_period??0).toLocaleString("tr-TR")),1),'
            'M("p",{class:"text-[10px] text-gray-500 font-bold mt-1 uppercase tracking-widest"},"Secilen Donem",-1)]),'
            'M("div",{class:"bg-[#111318] border border-white/5 rounded-2xl p-5"},['
            'M("p",{class:"text-[9px] font-black text-gray-600 uppercase tracking-widest"},"Tiklama",-1),'
            'M("p",{class:"text-3xl font-black text-amber-400 italic mt-2"},'
            'K((n.value?.sponsor_clicks_period??0).toLocaleString("tr-TR")),1),'
            'M("p",{class:"text-[10px] text-gray-500 font-bold mt-1 uppercase tracking-widest"},"Sponsor Tiklamasi",-1)]),'
            'M("div",{class:"bg-[#111318] border border-white/5 rounded-2xl p-5"},['
            'M("p",{class:"text-[9px] font-black text-gray-600 uppercase tracking-widest"},"Sayfa",-1),'
            'M("p",{class:"text-3xl font-black text-purple-400 italic mt-2"},'
            'K((n.value?.page_views_period??0).toLocaleString("tr-TR")),1),'
            'M("p",{class:"text-[10px] text-gray-500 font-bold mt-1 uppercase tracking-widest"},"Goruntulenme",-1)])]),'
            'M("div",{class:"bg-[#111318] border border-white/5 rounded-2xl p-5 mt-4"},['
            'M("p",{class:"text-sm font-black text-white uppercase tracking-tight mb-3"},"Sponsor Tiklamalari",-1),'
            'M("div",{class:"space-y-2"},[(bt(!0),Nt(Di,null,eo(b.value||[],(u,m)=>(bt(),Nt("div",{key:u.id||m,class:"flex items-center justify-between text-sm"},['
            'M("span",{class:"text-white/70 font-bold"},K(u.name),1),'
            'M("span",{class:"text-[var(--panel-primary)] font-black italic"},K(u.clicks),1)]))),128))])]),'
            'M("div",uh,['
        )
        patches.append((anchor, analytics_grid))

    return patches


def main() -> None:
    ensure_placeholder()

    market_delete_old = (
        'e("div",ve,[e("button",{onClick:h=>M(l),class:"w-10 h-10 rounded-xl bg-white/5 hover:bg-white/10 text-white/40 hover:text-white transition-all flex items-center justify-center border border-white/5"},[o(r(U),{class:"w-4 h-4"})],8,xe)])])'
    )
    market_delete_new = (
        'e("div",ve,[e("button",{onClick:h=>M(l),class:"w-10 h-10 rounded-xl bg-white/5 hover:bg-white/10 text-white/40 hover:text-white transition-all flex items-center justify-center border border-white/5"},[o(r(U),{class:"w-4 h-4"})],8,xe),e("button",{onClick:h=>(async()=>{if(confirm("Bu urun kalici olarak silinsin mi?"))try{await m.delete(`/admin/market/${l.id}`),d.success("Urun silindi."),j()}catch{d.error("Silinemedi.")}})(),class:"w-10 h-10 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-400 transition-all flex items-center justify-center border border-red-500/20",title:"Sil"},[o(r(H),{class:"w-4 h-4"})],8,["onClick"])])])'
    )

    market_img_old = 'src:l.image_path||l.image||"/placeholder.png"'
    market_img_new = 'src:l.image_path||l.image||"/placeholder.png",onerror:"this.onerror=null;this.src=\\"/placeholder.png\\""'

    market_preview_old = 'src:a.value.image_preview,class:"w-full h-full object-cover"'
    market_preview_new = 'src:a.value.image_preview||"/placeholder.png",onerror:"this.onerror=null;this.src=\\"/placeholder.png\\"",class:"w-full h-full object-cover"'

    market_edit_img_old = "image_preview:s.image_path,is_active:s.is_active"
    market_edit_img_new = "image_preview:s.image_path||s.image||s.image_preview||null,is_active:s.is_active"

    market_wallets_old = "required_wallets:s.required_wallets||[]"
    market_wallets_new = "required_wallets:Array.isArray(s.required_wallets)?s.required_wallets:[]"

    market_view_img_old = 'src:r.image_path||"/placeholder.png",class:"w-full h-full object-cover'
    market_view_img_new = (
        'src:r.image_path||r.image||"/placeholder.png",onerror:"this.onerror=null;this.src=\\"/placeholder.png\\"",'
        'class:"w-full h-full object-cover'
    )

    market_view_history_img_old = 'src:r.product.image_path,class:"w-full h-full object-cover'
    market_view_history_img_new = (
        'src:r.product.image_path||r.product?.image||"/placeholder.png",'
        'onerror:"this.onerror=null;this.src=\\"/placeholder.png\\"",class:"w-full h-full object-cover'
    )

    ticket_participations_load_old = (
        'r.status==="success"&&(_.value=r.data,_.value.length>0&&_.value[0]&&(h.value=_.value[0].id))'
    )
    ticket_participations_load_new = (
        'r.status==="success"&&(_.value=r.data,_.value.length>0&&_.value[0]&&'
        '(h.value=_.value[0].id,(async()=>{try{const x=await y.get(`/admin/ticket-events/${h.value}/participations`);'
        'x.status==="success"&&(Z.value=x.data)}catch{}})()))'
    )

    special_odds_home_logo_old = 'src:a.value==="my-bets"?e.event?.home_team?.logo_url:e.home_team?.logo_url,class:"w-full h-full object-contain",loading:"lazy"'
    special_odds_home_logo_new = (
        'src:a.value==="my-bets"?e.event?.home_team?.logo_url||"/placeholder.png":'
        'e.home_team?.logo_url||"/placeholder.png",onerror:"this.onerror=null;this.src=\\"/placeholder.png\\"",'
        'class:"w-full h-full object-contain",loading:"lazy"'
    )

    special_odds_away_logo_old = 'src:a.value==="my-bets"?e.event?.away_team?.logo_url:e.away_team?.logo_url,class:"w-full h-full object-contain",loading:"lazy"'
    special_odds_away_logo_new = (
        'src:a.value==="my-bets"?e.event?.away_team?.logo_url||"/placeholder.png":'
        'e.away_team?.logo_url||"/placeholder.png",onerror:"this.onerror=null;this.src=\\"/placeholder.png\\"",'
        'class:"w-full h-full object-contain",loading:"lazy"'
    )

    market_orders_fetch_old = 'const t=await S.get("/admin/market/orders?page="+a);d.value=t,k.value=a'
    market_orders_fetch_new = (
        'let t=null;try{t=await S.get("/admin/market/orders?page="+a)}catch{try{t=await S.get("/admin/market-orders?page="+a)}catch{}}'
        'const rows=Array.isArray(t?.data)?t.data:[];'
        'd.value={data:rows,current_page:t?.current_page??1,last_page:t?.last_page??1,total:t?.total??rows.length};k.value=a'
    )

    market_orders_empty_old = "d.value?.data.length===0"
    market_orders_empty_new = "!d.value?.data?.length"

    market_orders_approve_old = 'await S.post(`/admin/market/orders/${a}/approve`)'
    market_orders_approve_new = (
        'await S.post(`/admin/market/orders/${a}/approve`).catch(()=>S.post(`/admin/market-orders/${a}/approve`))'
    )

    market_orders_reject_old = 'await S.post(`/admin/market/orders/${a}/reject`)'
    market_orders_reject_new = (
        'await S.post(`/admin/market/orders/${a}/reject`).catch(()=>S.post(`/admin/market-orders/${a}/reject`))'
    )

    news_old = 'class:"bg-white/5 border border-white/10 rounded-[2rem] overflow-hidden"'
    news_new = 'class:"bg-white/5 border border-white/10 rounded-[2rem] overflow-visible"'

    news_modal_old = 'class:"fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm overflow-hidden"'
    news_modal_new = 'class:"fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm overflow-visible"'

    music_match_old = 'g=s=>s.match(/(?:v=|youtu\\.be\\/)([^&\\n?#]+)/)?.[1]??null'
    music_match_new = 'g=s=>(s||"").match(/(?:v=|youtu\\.be\\/)([^&\\n?#]+)/)?.[1]??null'

    special_odds_gorilla_old = 'a.value==="active"?(i(),y(l(b),{key:1,class:"w-32 h-32 text-gray-400"}))'
    special_odds_gorilla_new = 'a.value==="active"?(i(),y(l(j),{key:1,class:"w-16 h-16 text-gray-400/30"}))'

    sponsor_click_old = "const c=(m,f)=>{t.settings.sponsor_click_modal&&(m.preventDefault(),o(f))}"
    sponsor_click_new = (
        "const c=(m,f)=>{ne.post(`/sponsors/${f.id}/click`).catch(()=>{});"
        "t.settings.sponsor_click_modal&&(m.preventDefault(),o(f))}"
    )

    sponsor_detailed_old = 'href:k.link,target:"_blank",class:W(["group relative bg-[#0c0c0e]'
    sponsor_detailed_new = 'href:k.link,target:"_blank",onClick:S=>c(S,k),class:W(["group relative bg-[#0c0c0e]'

    sponsor_carousel_old = 'href:g.link,target:"_blank",class:"w-32 md:w-48'
    sponsor_carousel_new = (
        'href:g.link,target:"_blank",onClick:S=>{ne.post(`/sponsors/${g.id}/click`).catch(()=>{})},'
        'class:"w-32 md:w-48'
    )

    analytics_bootstrap = (
        ';(function(){const p=()=>fetch("/api/analytics/ping",{method:"POST",credentials:"include"}).catch(()=>{});'
        'fetch("/api/analytics/visit",{method:"POST",credentials:"include",headers:{"Content-Type":"application/json"},'
        'body:JSON.stringify({path:location.pathname})}).catch(()=>{});setInterval(p,60000);})();'
    )

    changed = 0
    for base in TARGETS:
        market_path = base / "MarketManagement-BLGnBLI7.js"
        if patch_file(
            market_path,
            [
                (market_delete_old, market_delete_new),
                (market_img_old, market_img_new),
                (market_preview_old, market_preview_new),
                (market_edit_img_old, market_edit_img_new),
                (market_wallets_old, market_wallets_new),
            ],
        ):
            changed += 1
            print(f"Patched {market_path}")

        news_path = base / "NewsManagement-DcFS_Q5z.js"
        if patch_file(news_path, [(news_old, news_new), (news_modal_old, news_modal_new)]):
            changed += 1
            print(f"Patched {news_path}")

        music_path = base / "MusicManagement-d-RSM6WN.js"
        if patch_file(music_path, [(music_match_old, music_match_new)]):
            changed += 1
            print(f"Patched {music_path}")

        odds_path = base / "SpecialOddsView-BKhmsouc.js"
        if patch_file(
            odds_path,
            [
                (special_odds_gorilla_old, special_odds_gorilla_new),
                (special_odds_home_logo_old, special_odds_home_logo_new),
                (special_odds_away_logo_old, special_odds_away_logo_new),
            ],
        ):
            changed += 1
            print(f"Patched {odds_path}")

        market_view_path = base / "MarketView-JdKbPNN7.js"
        if patch_file(
            market_view_path,
            [
                (market_view_img_old, market_view_img_new),
                (market_view_history_img_old, market_view_history_img_new),
            ],
        ):
            changed += 1
            print(f"Patched {market_view_path}")

        ticket_parts_path = base / "TicketParticipations-C1Ww6ilT.js"
        if patch_file(ticket_parts_path, [(ticket_participations_load_old, ticket_participations_load_new)]):
            changed += 1
            print(f"Patched {ticket_parts_path}")

        market_orders_path = base / "MarketOrders-FkSjWGyH.js"
        if patch_file(
            market_orders_path,
            [
                (market_orders_fetch_old, market_orders_fetch_new),
                (market_orders_empty_old, market_orders_empty_new),
                (market_orders_approve_old, market_orders_approve_new),
                (market_orders_reject_old, market_orders_reject_new),
            ],
        ):
            changed += 1
            print(f"Patched {market_orders_path}")

        stats_path = base / "Statistics-BEqMqc3u.js"
        if stats_path.exists():
            stats_text = stats_path.read_text(encoding="utf-8")
            stats_patches = build_statistics_patches(stats_text)
            if patch_file(stats_path, stats_patches):
                changed += 1
                print(f"Patched {stats_path}")

        index_path = base / "index-ChvzUPTI.js"
        if patch_file(
            index_path,
            [
                (sponsor_click_old, sponsor_click_new),
                (sponsor_detailed_old, sponsor_detailed_new),
                (sponsor_carousel_old, sponsor_carousel_new),
            ],
        ):
            changed += 1
            print(f"Patched {index_path}")
        if index_path.exists():
            text = index_path.read_text(encoding="utf-8")
            if "/api/analytics/visit" not in text:
                index_path.write_text(text + analytics_bootstrap, encoding="utf-8")
                changed += 1
                print(f"Appended analytics bootstrap to {index_path}")

    print(f"Done. {changed} file(s) updated.")


if __name__ == "__main__":
    main()
