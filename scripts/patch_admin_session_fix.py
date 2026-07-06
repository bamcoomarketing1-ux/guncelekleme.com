#!/usr/bin/env python3
"""Fix admin panel logout loop: token selection + 401 handler + visibilitychange."""

from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
TARGETS = [
    ROOT / "public/assets/index-ChvzUPTI.js",
    ROOT / "resources/frontend/assets/index-ChvzUPTI.js",
]

OLD_REQUEST = (
    'ne.interceptors.request.use(e=>{const t=window.location.pathname.startsWith("/panel"),'
    's=localStorage.getItem(t?"admin_token":"auth_token");'
    'return s&&(e.headers.Authorization=`Bearer ${s}`),e},e=>Promise.reject(e));'
)

NEW_REQUEST = (
    'ne.interceptors.request.use(e=>{const u=String(e.url||""),'
    'm=(e.method||"get").toLowerCase(),'
    'a=u.startsWith("/admin")||u==="/admin/login"||(u==="/settings"&&m==="post"),'
    't=a?localStorage.getItem("admin_token"):localStorage.getItem("auth_token");'
    'return t&&(e.headers.Authorization=`Bearer ${t}`),e},e=>Promise.reject(e));'
)

OLD_401 = (
    'if(e.response.status===401){const s=e.config.url?.endsWith("/login"),'
    'n=window.location.pathname.startsWith("/panel");'
    's||(n?(localStorage.removeItem("admin_token"),localStorage.removeItem("admin_data"),'
    'window.location.pathname!=="/panel/login"&&(window.location.href="/panel/login")):'
    '(localStorage.removeItem("auth_token"),localStorage.removeItem("user_data"),'
    'window.location.pathname!=="/"&&(window.location.href="/")))}'
)

NEW_401 = (
    'if(e.response.status===401){const u=String(e.config.url||""),'
    's=u.endsWith("/login"),'
    'a=u.startsWith("/admin")||(u==="/settings"&&(e.config.method||"get").toLowerCase()==="post"),'
    'p=window.location.pathname.startsWith("/panel");'
    's||(a?(localStorage.removeItem("admin_token"),localStorage.removeItem("admin_data"),'
    'window.location.pathname!=="/panel/login"&&(window.location.href="/panel/login")):'
    '!p&&!u.startsWith("/admin")&&(localStorage.removeItem("auth_token"),'
    'localStorage.removeItem("user_data"),window.location.pathname!=="/"&&(window.location.href="/")))}'
)

OLD_VISIBILITY = (
    'document.addEventListener("visibilitychange",()=>{'
    'document.visibilityState==="visible"&&e.value&&a()})'
)

NEW_VISIBILITY = (
    'document.addEventListener("visibilitychange",()=>{'
    'document.visibilityState==="visible"&&e.value&&'
    '!window.location.pathname.startsWith("/panel")&&a()})'
)


def patch_file(path: Path) -> list[str]:
    text = path.read_text(encoding="utf-8")
    changes: list[str] = []

    for name, old, new in [
        ("request interceptor", OLD_REQUEST, NEW_REQUEST),
        ("401 handler", OLD_401, NEW_401),
        ("visibilitychange", OLD_VISIBILITY, NEW_VISIBILITY),
    ]:
        if old not in text:
            if new in text:
                changes.append(f"{name}: already patched")
            else:
                changes.append(f"{name}: pattern not found")
            continue
        text = text.replace(old, new, 1)
        changes.append(f"{name}: patched")

    path.write_text(text, encoding="utf-8")
    return changes


def main() -> None:
    for target in TARGETS:
        if not target.is_file():
            print(f"SKIP missing {target}")
            continue
        print(f"\n{target}:")
        for line in patch_file(target):
            print(f"  - {line}")


if __name__ == "__main__":
    main()
