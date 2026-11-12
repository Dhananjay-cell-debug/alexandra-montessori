"""Alexandra Montessori - October 2026 poster set (Instagram 4:5).

Codex makes cut-outs (assets/: children, practitioner, 3D clay icons) and photo plates
(plates/). This script lays out each poster in HTML and renders it with headless Chrome
at 2x, downsampled to 1080x1350, into posters/.

Usage:  python posters.py            # all
        python posters.py admissions # one or more by key
"""
import subprocess
import sys
from pathlib import Path

from PIL import Image

ROOT = Path(__file__).resolve().parent
A = ROOT / "assets"
PL = ROOT / "plates"
FONTS = ROOT / "fonts"
OUT = ROOT / "posters"
HTMLD = ROOT / "_html_posters"
BADGE = (ROOT / "../../../src/assets/logo-badge.webp").resolve()
CHROME = r"C:\Program Files\Google\Chrome\Application\chrome.exe"
W, H = 1080, 1350


def u(p):
    return Path(p).resolve().as_uri()


def asset(name):
    """Latest version of an asset (name-v2.png beats name.png)."""
    stem = name[:-4]
    cands = sorted(A.glob(f"{stem}*.png"), key=lambda p: p.stat().st_mtime)
    return u(cands[-1]) if cands else ""


CSS = f"""
@font-face {{ font-family: Fraunces; src: url('{u(FONTS / "Fraunces.ttf")}'); font-weight: 100 900; }}
@font-face {{ font-family: Fraunces; src: url('{u(FONTS / "Fraunces-Italic.ttf")}'); font-style: italic; font-weight: 100 900; }}
@font-face {{ font-family: Caveat; src: url('{u(FONTS / "Caveat.ttf")}'); font-weight: 400 700; }}
@font-face {{ font-family: Poppins; src: url('{u(FONTS / "poppins-latin-400.woff2")}'); font-weight: 400; }}
@font-face {{ font-family: Poppins; src: url('{u(FONTS / "poppins-latin-500.woff2")}'); font-weight: 500; }}
@font-face {{ font-family: Poppins; src: url('{u(FONTS / "poppins-latin-600.woff2")}'); font-weight: 600; }}
@font-face {{ font-family: Poppins; src: url('{u(FONTS / "poppins-latin-700.woff2")}'); font-weight: 700; }}
:root {{
  --laurel: #2f4a35; --laurel-2: #4f6b4c; --sage: #a3bc9a; --sage-2: #cfdcc8; --sage-3: #e6eee1;
  --cream: #f7f2ea; --paper: #fffdf8; --apricot: #e8875a; --apricot-2: #f6c7a8; --butter: #f2cf6b;
  --blush: #f2b8b0; --ink: #2d3a31; --muted: #5b6a60;
}}
* {{ margin: 0; padding: 0; box-sizing: border-box; }}
html, body {{ width: {W}px; height: {H}px; }}
.c {{ position: relative; width: {W}px; height: {H}px; overflow: hidden; background: var(--cream);
      font-family: Poppins; color: var(--ink); -webkit-font-smoothing: antialiased; }}
.pattern {{ position: absolute; inset: 0; opacity: .55; }}
.abs {{ position: absolute; }}
.badge {{ position: absolute; left: 56px; top: 48px; width: 124px; height: 124px; border-radius: 50%;
          box-shadow: 0 8px 24px rgba(47,74,53,.12); }}
.url {{ position: absolute; right: 60px; top: 98px; font-size: 16px; font-weight: 500; letter-spacing: .04em; color: var(--laurel-2); }}
.chip {{ display: inline-flex; align-items: center; gap: 10px; padding: 9px 18px; border-radius: 999px;
         background: var(--sage-3); color: var(--laurel); font-size: 14px; font-weight: 600; letter-spacing: .14em;
         text-transform: uppercase; border: 1.5px solid var(--sage-2); }}
.chip i {{ width: 7px; height: 7px; border-radius: 50%; background: var(--apricot); display: inline-block; }}
.h {{ font-family: Fraunces; font-weight: 750; font-variation-settings: "SOFT" 100, "WONK" 0, "opsz" 144;
      color: var(--laurel); letter-spacing: -.03em; line-height: .92; }}
.h em {{ font-style: italic; font-weight: 600; color: var(--apricot); font-variation-settings: "SOFT" 100, "WONK" 1, "opsz" 144; }}
.sub {{ font-size: 22px; line-height: 1.45; color: var(--muted); }}
.sub b {{ color: var(--laurel); font-weight: 600; }}
.cta {{ display: inline-flex; align-items: center; gap: 14px; padding: 18px 20px 18px 30px; border-radius: 999px;
        background: var(--laurel); color: #fff; font-size: 21px; font-weight: 600; box-shadow: 0 10px 26px rgba(47,74,53,.25); }}
.cta .ar {{ width: 38px; height: 38px; border-radius: 50%; background: var(--butter); color: var(--laurel);
            display: grid; place-items: center; font-size: 20px; font-weight: 700; }}
.ticks {{ list-style: none; display: grid; gap: 14px; }}
.ticks li {{ display: flex; align-items: center; gap: 14px; font-size: 20px; font-weight: 500; color: var(--ink); }}
.ticks li span.t {{ flex: none; width: 32px; height: 32px; border-radius: 50%; background: var(--sage-3);
                    border: 1.5px solid var(--sage); display: grid; place-items: center; }}
.foot {{ position: absolute; left: 0; right: 0; bottom: 0; height: 118px; background: var(--laurel);
         display: grid; grid-template-columns: 1fr 1fr 1fr; align-items: center; padding: 0 34px; color: #fff; }}
.foot > div {{ display: flex; align-items: center; gap: 14px; justify-content: center; position: relative; }}
.foot > div + div::before {{ content: ""; position: absolute; left: 0; top: 8px; bottom: 8px; width: 1px; background: rgba(255,255,255,.22); }}
.foot .pin {{ width: 40px; height: 40px; border-radius: 50%; background: rgba(255,255,255,.1); display: grid; place-items: center; }}
.foot .n {{ font-size: 15px; font-weight: 600; letter-spacing: .14em; text-transform: uppercase; color: var(--sage-2); }}
.foot .p {{ font-size: 21px; font-weight: 600; letter-spacing: .01em; margin-top: 2px; }}
.arch {{ position: absolute; border-radius: 999px 999px 0 0; overflow: hidden; }}
.cut {{ position: absolute; filter: drop-shadow(0 18px 24px rgba(47,74,53,.18)); }}
.ico {{ position: absolute; filter: drop-shadow(0 10px 14px rgba(47,74,53,.16)); }}
.fine {{ font-size: 13px; line-height: 1.5; color: var(--muted); }}
.hand {{ font-family: Caveat; font-weight: 700; color: var(--laurel-2); }}
"""

PIN = """<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#f2cf6b" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>"""
TICK = """<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2f4a35" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>"""

# faint hand-drawn laurel-leaf pattern for the cream ground
PATTERN = """<svg class="pattern" width="1080" height="1350" xmlns="http://www.w3.org/2000/svg"><defs>
<pattern id="lf" width="180" height="180" patternUnits="userSpaceOnUse" patternTransform="rotate(-18)">
<g fill="none" stroke="#c9d6c1" stroke-width="1.6" stroke-linecap="round">
<path d="M30 60c14-18 34-20 44-14-8 14-28 22-44 14Z"/><path d="M30 60 78 44"/>
<circle cx="130" cy="40" r="5"/><path d="M110 130c10 6 22 6 30-2"/><path d="M60 150l6 6m0-6-6 6"/>
<path d="M140 100c-6-16 0-30 10-36 6 12 2 28-10 36Z"/></g></pattern></defs>
<rect width="1080" height="1350" fill="url(#lf)"/></svg>"""


def dashed(d, color="#a3bc9a"):
    return f"""<svg class="abs" style="left:0;top:0" width="1080" height="1350"><path d="{d}" fill="none" stroke="{color}" stroke-width="2.4" stroke-dasharray="2 11" stroke-linecap="round"/></svg>"""


def foot():
    rows = [("Hounslow", "0208 001 5165"), ("Heston", "0203 627 6707"), ("Hammersmith", "0204 618 3477")]
    cells = "".join(f'<div><span class="pin">{PIN}</span><span><div class="n">{n}</div><div class="p">{p}</div></span></div>' for n, p in rows)
    return f'<div class="foot">{cells}</div>'


def page(inner, url=True):
    return f"""<!doctype html><html><head><meta charset="utf-8"><style>{CSS}</style></head><body><div class="c">
{PATTERN}
<img class="badge" src="{u(BADGE)}">
{'<div class="url">alexandramontessori.co.uk</div>' if url else ''}
{inner}
{foot()}
</div></body></html>"""


def ico(name, x, y, w, rot=0):
    src = asset(name)
    return f'<img class="ico" src="{src}" style="left:{x}px;top:{y}px;width:{w}px;transform:rotate({rot}deg)">' if src else ""


def ticks(items):
    return '<ul class="ticks">' + "".join(f'<li><span class="t">{TICK}</span>{t}</li>' for t in items) + "</ul>"


# ------------------------------------------------------------------ posters
def p_admissions():
    return page(f"""
<div class="abs" style="left:60px;top:226px"><span class="chip"><i></i>Babies to 5 years</span></div>
<div class="abs h" style="left:56px;top:290px;font-size:132px">Admissions<br><em>now open</em></div>
<p class="abs sub" style="left:60px;top:560px;width:430px">A calm, Montessori start to life, three West London nurseries, <b>Mon&ndash;Fri, 8am&ndash;6pm</b>.</p>
<div class="abs" style="left:60px;top:700px;width:440px">{ticks(["Montessori + EYFS curriculum", "Funded hours from 9 months", "A key person for every child", "Freshly prepared meals"])}</div>
<div class="abs" style="left:60px;top:1000px"><span class="cta">Book a visit <span class="ar">&rarr;</span></span></div>
<div class="arch" style="left:520px;top:400px;width:500px;height:832px;background:linear-gradient(180deg,#cfdcc8,#a3bc9a)"></div>
<div class="arch" style="left:548px;top:428px;width:444px;height:804px;border:2px dashed rgba(255,255,255,.7);background:none"></div>
<img class="cut" src="{asset('kid-girl-3-pink-cube.png')}" style="left:430px;top:470px;width:640px">
{ico('icon-pink-tower.png', 880, 300, 150, 6)}
{ico('icon-rainbow-stacker.png', 470, 1052, 150, -8)}
{ico('icon-laurel.png', 946, 690, 120, 20)}
{dashed("M 560 1030 C 470 980, 520 880, 610 860")}
""")


def p_funding():
    cards = [
        ("#a3bc9a", "#2f4a35", "9", "months+", "30 hrs", "a week for eligible working parents"),
        ("#f2cf6b", "#5a4512", "2", "years", "15&ndash;30", "hours for eligible families and working parents"),
        ("#f0a979", "#5b2a12", "3&ndash;4", "years", "15 hrs", "for every child, plus 15 more for working parents"),
    ]
    html = ""
    for i, (bg, fg, big, unit, hrs, txt) in enumerate(cards):
        x = 56 + i * 330
        html += f"""<div class="abs" style="left:{x}px;top:760px;width:308px;height:400px;border-radius:30px;background:{bg};color:{fg};padding:30px 28px;box-shadow:0 14px 30px rgba(47,74,53,.12)">
  <div style="font-size:14px;font-weight:600;letter-spacing:.16em;text-transform:uppercase;opacity:.8">From</div>
  <div style="font-family:Fraunces;font-weight:800;font-variation-settings:'SOFT' 100,'opsz' 144;font-size:96px;line-height:.95;letter-spacing:-.03em;margin-top:6px">{big}</div>
  <div style="font-family:Fraunces;font-style:italic;font-weight:600;font-size:30px;margin-top:-2px">{unit}</div>
  <div style="height:2px;background:currentColor;opacity:.2;margin:22px 0 18px"></div>
  <div style="font-size:34px;font-weight:700;letter-spacing:-.01em">{hrs}</div>
  <div style="font-size:18px;line-height:1.4;margin-top:6px;font-weight:500">{txt}</div>
</div>"""
    return page(f"""
<div class="abs" style="left:60px;top:226px"><span class="chip"><i></i>Funded childcare &middot; England</span></div>
<div class="abs h" style="left:56px;top:290px;font-size:112px">Up to 30<br>funded <em>hours</em></div>
<p class="abs sub" style="left:60px;top:540px;width:520px">We accept every government entitlement, and we&rsquo;ll walk you through yours, step by step.</p>
<div class="abs" style="left:60px;top:660px"><span class="cta" style="font-size:19px">Ask us: 0204 618 3477 <span class="ar">&rarr;</span></span></div>
<div class="abs" style="left:640px;top:250px;width:390px;height:390px;border-radius:50%;background:radial-gradient(circle at 40% 35%,#fff6e3,#f2cf6b)"></div>
<img class="cut" src="{asset('kid-baby-10m-sitting.png')}" style="left:600px;top:225px;width:460px">
{ico('icon-heart-hands.png', 590, 560, 120, -10)}
<p class="abs fine" style="left:60px;right:60px;top:1180px">Funded hours are up to 38 weeks a year (or stretched across the year). Eligibility applies; check yours on GOV.UK. 2-year-old funding: 15 hours for families receiving certain support, 30 hours for eligible working parents. Meals, consumables and extra hours may be charged. Hammersmith welcomes children from 12 months.</p>
""")


def circle(src, x, y, d, label, bg, fg="#fff", lx=0, ly=0):
    return f"""<div class="abs" style="left:{x}px;top:{y}px;width:{d}px;height:{d}px;border-radius:50%;overflow:hidden;border:7px solid #fff;box-shadow:0 14px 30px rgba(47,74,53,.18)">
<img src="{src}" style="width:100%;height:100%;object-fit:cover"></div>
<div class="abs" style="left:{x + lx}px;top:{y + d - 30 + ly}px;background:{bg};color:{fg};padding:10px 18px;border-radius:14px;font-size:19px;font-weight:600;line-height:1.2;box-shadow:0 8px 18px rgba(47,74,53,.16);white-space:nowrap">{label}</div>"""


def p_learning():
    pl = lambda n: u(PL / n)
    return page(f"""
<div class="abs h" style="left:0;right:0;top:206px;text-align:center;font-size:84px">It looks like <em>play.</em></div>
<div class="abs" style="left:0;right:0;top:300px;text-align:center;font-family:Fraunces;font-weight:500;font-variation-settings:'SOFT' 100,'opsz' 72;font-size:40px;color:var(--laurel-2)">Here&rsquo;s what it really is.</div>
{dashed("M 270 560 C 380 600, 420 640, 480 700")}{dashed("M 810 560 C 700 600, 660 640, 600 700")}
{dashed("M 270 900 C 360 880, 400 860, 450 840")}{dashed("M 810 900 C 720 880, 680 860, 630 840")}
{circle(pl('a1-water-pouring.png'), 70, 390, 230, 'Independence', '#4f6b4c', lx=10)}
{circle(pl('b1-practitioner-presenting.png'), 780, 390, 230, 'Early maths', '#e8875a', lx=40)}
{circle(pl('a3-bead-threading.png'), 70, 790, 230, 'Fine motor skills', '#f2cf6b', '#4a3a0e', lx=-6)}
{circle(pl('b3-snack-table.png'), 780, 790, 230, 'Grace &amp; courtesy', '#a3bc9a', '#2f4a35', lx=10)}
<div class="abs" style="left:330px;top:560px;width:420px;height:560px;border-radius:999px 999px 0 0;background:linear-gradient(180deg,#e6eee1,#cfdcc8)"></div>
<img class="cut" src="{asset('kid-boy-4-pointing.png')}" style="left:300px;top:450px;width:480px">
<div class="abs" style="left:0;right:0;top:1150px;text-align:center"><span style="display:inline-block;background:#fff;padding:16px 34px;border-radius:999px;font-size:22px;font-weight:600;color:var(--laurel);box-shadow:0 10px 24px rgba(47,74,53,.12)">That&rsquo;s the Montessori way <span style="color:var(--apricot)">&#10047;</span></span></div>
""")


DAY = [("08:00", "Arrive &amp; settle with their key person"), ("09:00", "Circle time"), ("09:15", "Montessori work cycle"),
       ("11:00", "Outdoors &amp; nature"), ("12:15", "Lunch, family-style"), ("12:45", "Rest &amp; naps"),
       ("13:15", "Crafts, cooking &amp; science"), ("15:30", "Afternoon tea"), ("16:00", "Independent work"),
       ("18:00", "Home time, full of stories")]


def p_day():
    rows = ""
    for i, (t, txt) in enumerate(DAY):
        y = 470 + i * 64
        dot = "#e8875a" if i in (0, len(DAY) - 1) else "#a3bc9a"
        rows += f"""<div class="abs" style="left:60px;top:{y}px;display:flex;align-items:center;gap:22px">
<span style="width:96px;font-family:Fraunces;font-weight:700;font-variation-settings:'SOFT' 100,'opsz' 72;font-size:28px;color:var(--laurel)">{t}</span>
<span style="width:18px;height:18px;border-radius:50%;background:{dot};border:4px solid var(--cream);box-shadow:0 0 0 2px {dot}"></span>
<span style="font-size:20px;font-weight:500">{txt}</span></div>"""
    return page(f"""
<div class="abs" style="left:60px;top:226px"><span class="chip"><i></i>Mon&ndash;Fri &middot; 8am&ndash;6pm</span></div>
<div class="abs h" style="left:56px;top:290px;font-size:112px">A day at <em>Alexandra</em></div>
<div class="abs" style="left:186px;top:490px;width:2px;height:580px;background:repeating-linear-gradient(to bottom,#a3bc9a 0 6px,transparent 6px 14px)"></div>
{rows}
<div class="arch" style="left:690px;top:560px;width:340px;height:672px;background:linear-gradient(180deg,#fbe3cf,#f0a979)"></div>
<img class="cut" src="{asset('kid-toddler-2-watering-can.png')}" style="left:630px;top:600px;width:440px">
{ico('icon-clock.png', 880, 430, 150, 8)}
{ico('icon-apple.png', 640, 1080, 110, -6)}
""")


def p_hiring():
    return page(f"""
<div class="abs" style="left:60px;top:226px"><span class="chip"><i></i>Careers &middot; West London</span></div>
<div class="abs h" style="left:56px;top:290px;font-size:136px">We&rsquo;re<br><em>hiring</em></div>
<p class="abs sub" style="left:60px;top:590px;width:470px"><b>Room Leaders, Nursery Practitioners &amp; Nursery Assistants</b> for Hounslow, Heston and Hammersmith.</p>
<div class="abs" style="left:60px;top:740px;width:480px">{ticks(["Real training, genuine support", "A path from apprentice to manager", "Authentic Montessori practice", "A close, kind team"])}</div>
<div class="abs" style="left:60px;top:1040px"><span class="cta" style="font-size:19px">alexandramontessori.co.uk/careers <span class="ar">&rarr;</span></span></div>
<div class="arch" style="left:560px;top:380px;width:470px;height:852px;background:linear-gradient(180deg,#e6eee1,#a3bc9a)"></div>
<img class="cut" src="{asset('adult-practitioner.png')}" style="left:470px;top:440px;width:620px">
{ico('icon-golden-beads.png', 900, 320, 140, 10)}
{ico('icon-pencil-paper.png', 540, 1040, 130, -10)}
""")


POSTERS = {
    "admissions": ("01-admissions-now-open", p_admissions),
    "funding": ("02-funded-childcare", p_funding),
    "learning": ("03-looks-like-play", p_learning),
    "day": ("04-a-day-at-alexandra", p_day),
    "hiring": ("05-we-are-hiring", p_hiring),
}


def render(key):
    name, fn = POSTERS[key]
    OUT.mkdir(exist_ok=True); HTMLD.mkdir(exist_ok=True)
    h = HTMLD / f"{name}.html"
    h.write_text(fn(), encoding="utf-8")
    big = OUT / f"_{name}@2x.png"
    subprocess.run([CHROME, "--headless=new", "--disable-gpu", "--hide-scrollbars", "--allow-file-access-from-files",
                    f"--window-size={W},{H}", "--force-device-scale-factor=2", "--virtual-time-budget=4000",
                    f"--screenshot={big}", h.as_uri()], check=True, capture_output=True)
    Image.open(big).convert("RGB").resize((W, H), Image.LANCZOS).save(OUT / f"{name}.png", optimize=True)
    big.unlink()
    print(f"  ok {key} -> posters/{name}.png")


if __name__ == "__main__":
    for k in sys.argv[1:] or list(POSTERS):
        render(k)
