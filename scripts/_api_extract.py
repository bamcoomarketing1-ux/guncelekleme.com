import re
from pathlib import Path

assets = Path(__file__).resolve().parent.parent / "public/assets"

# AccountSponsors full script (minified one line)
t = (assets / "AccountSponsors-DgcS_b2u.js").read_text(encoding="utf-8")
print("SPONSORS len", len(t))
for kw in ["is_connected", "username", "logo", "name", "link", "message", "status"]:
    print(kw, t.count(kw))

# find template fields
idx = t.find("is_connected")
print(t[max(0,idx-200):idx+400].encode('ascii','replace').decode())

print("\n--- TICKETS ---")
t2 = (assets / "AccountTickets-CBv855Pq.js").read_text(encoding="utf-8")
for kw in ["tickets", "requests", "ticket_event", "status", "length"]:
    if kw in t2:
        pass
idx = t2.find("participation-history")
print(t2[max(0,idx-100):idx+500].encode('ascii','replace').decode())

# more ticket rendering
for m in re.finditer(r'm\.value\.|f\.value\.', t2):
    pass
print("tickets refs", len(re.findall(r'm\.value', t2)), "requests", len(re.findall(r'f\.value', t2)))

print("\n--- VERIFY ---")
t3 = (assets / "AccountVerify-CSPm5zhY.js").read_text(encoding="utf-8")
for ep in ["/account/telegram/status", "/account/telegram/generate-code", "/resend-verification", "/verify-email"]:
    i = t3.find(ep)
    if i >= 0:
        print(t3[i:i+200].encode('ascii','replace').decode())
        print("---")
