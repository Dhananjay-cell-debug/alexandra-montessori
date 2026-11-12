"""Alexandra Montessori - October 2026 Instagram set.

Codex makes the photographic plates (plates/*.png, no text). This script:
  1. grades every plate the same way (one photographer, one series),
  2. typesets each post as HTML (exact copy, real logo, real numbers),
  3. renders with headless Chrome at 2x and downsamples to 1080x1350.

Usage:  python build.py            # all posts whose plate exists
        python build.py p3 p5      # only these
"""
import subprocess
import sys
from pathlib import Path

import numpy as np
from PIL import Image, ImageEnhance, ImageFilter

ROOT = Path(__file__).resolve().parent
PLATES = ROOT / "plates"
GRADED = ROOT / "_graded"
HTML = ROOT / "_html"
OUT = ROOT / "final"
FONTS = ROOT / "fonts"
BADGE = (ROOT / "../../../src/assets/logo-badge.webp").resolve()
CHROME = r"C:\Program Files\Google\Chrome\Application\chrome.exe"
W, H = 1080, 1350


def uri(p):
    return Path(p).resolve().as_uri()


# ------------------------------------------------------------------ grading
def grade(src, dst):
    im = Image.open(src).convert("RGB")
    # gentle warmth + slightly lifted blacks + a touch less saturation
    r, g, b = im.split()
    r = r.point(lambda v: min(255, int(v * 1.02 + 2)))
    b = b.point(lambda v: int(v * 0.97))
    im = Image.merge("RGB", (r, g, b))
    im = im.point(lambda v: int(14 + v * (241 / 255)))
    im = ImageEnhance.Color(im).enhance(0.92)
    im = ImageEnhance.Contrast(im).enhance(1.03)
    # fine monochrome grain, same seed for every plate
    a = np.asarray(im).astype(np.float32)
    grain = np.random.default_rng(7).normal(0, 5.5, a.shape[:2]).astype(np.float32)
    grain = np.asarray(Image.fromarray(grain + 128).convert("L").filter(ImageFilter.GaussianBlur(0.5))).astype(np.float32) - 128
    a = np.clip(a + grain[..., None], 0, 255).astype(np.uint8)
    Image.fromarray(a).save(dst, quality=95)


# ------------------------------------------------------------------ html
CSS = f"""
@font-face {{ font-family: Fraunces; src: url('{uri(FONTS / "Fraunces.ttf")}'); font-style: normal; font-weight: 100 900; }}
@font-face {{ font-family: Fraunces; src: url('{uri(FONTS / "Fraunces-Italic.ttf")}'); font-style: italic; font-weight: 100 900; }}
@font-face {{ font-family: Caveat; src: url('{uri(FONTS / "Caveat.ttf")}'); font-weight: 400 700; }}
@font-face {{ font-family: Poppins; src: url('{uri(FONTS / "poppins-latin-400.woff2")}'); font-weight: 400; }}
@font-face {{ font-family: Poppins; src: url('{uri(FONTS / "poppins-latin-500.woff2")}'); font-weight: 500; }}
@font-face {{ font-family: Poppins; src: url('{uri(FONTS / "poppins-latin-600.woff2")}'); font-weight: 600; }}
:root {{
  --laurel: #2c3a30; --ink: #36463d; --sage: #5f7a5a; --sage-soft: #a3bc9a;
  --cream: #f5f1ef; --oat: #ece4d8;
}}
* {{ margin: 0; padding: 0; box-sizing: border-box; }}
html, body {{ width: {W}px; height: {H}px; background: var(--cream); }}
.c {{ position: relative; width: {W}px; height: {H}px; overflow: hidden; font-family: Poppins; color: var(--ink);
      -webkit-font-smoothing: antialiased; text-rendering: geometricPrecision; }}
.plate {{ position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }}
.scrim-top {{ position: absolute; left: 0; right: 0; top: 0; pointer-events: none; }}
.scrim-bot {{ position: absolute; left: 0; right: 0; bottom: 0; height: 230px;
             background: linear-gradient(to top, rgba(28,34,29,.55), rgba(28,34,29,0)); }}
.badge {{ position: absolute; left: 64px; top: 60px; width: 118px; height: 118px; border-radius: 50%;
          box-shadow: 0 6px 22px rgba(40,50,42,.14); }}
.eyebrow {{ font-weight: 600; font-size: 15px; letter-spacing: .24em; text-transform: uppercase; color: var(--sage); }}
.eyebrow-tr {{ position: absolute; right: 64px; top: 108px; text-align: right; }}
h1 {{ font-family: Fraunces; font-weight: 380; font-variation-settings: "SOFT" 100, "WONK" 0, "opsz" 144;
      color: var(--laurel); letter-spacing: -.025em; line-height: .98; }}
h1 em {{ font-style: italic; font-weight: 340; color: var(--sage); font-variation-settings: "SOFT" 100, "WONK" 1, "opsz" 144; }}
.body {{ font-size: 23px; line-height: 1.55; color: var(--ink); }}
.foot {{ position: absolute; left: 64px; right: 64px; bottom: 52px; display: flex; justify-content: space-between;
         align-items: center; color: #fff; font-size: 15px; letter-spacing: .06em; }}
.foot .loc {{ font-weight: 500; letter-spacing: .2em; text-transform: uppercase; font-size: 13px; opacity: .92; }}
.foot .url {{ font-weight: 500; }}
.foot .dot {{ display: inline-block; width: 4px; height: 4px; border-radius: 50%; background: var(--sage-soft);
              margin: 0 12px 3px; }}
.pill {{ display: inline-flex; align-items: center; gap: 14px; background: var(--laurel); color: var(--cream);
         border-radius: 999px; padding: 18px 30px; font-weight: 500; font-size: 19px; letter-spacing: .01em; }}
.pill b {{ font-weight: 600; color: #dfe9d9; }}
.rule {{ width: 56px; height: 2px; background: var(--sage-soft); }}
.fine {{ font-size: 14px; line-height: 1.5; color: #5d6b62; }}
"""

FOOT = """<div class="scrim-bot"></div>
<div class="foot"><span class="url">alexandramontessori.co.uk</span>
<span class="loc">Hounslow<span class="dot"></span>Heston<span class="dot"></span>Hammersmith</span></div>"""


def page(plate, inner, pos="50% 50%", scrim=None, foot=True, badge=True):
    scrim_html = f'<div class="scrim-top" style="{scrim}"></div>' if scrim else ""
    return f"""<!doctype html><html><head><meta charset="utf-8"><style>{CSS}</style></head><body>
<div class="c">
  <img class="plate" src="{uri(GRADED / plate)}" style="object-position:{pos}">
  {scrim_html}
  {'<img class="badge" src="' + uri(BADGE) + '">' if badge else ''}
  {inner}
  {FOOT if foot else ''}
</div></body></html>"""


CREAM_FADE = lambda h, a=.9: f"height:{h}px;background:linear-gradient(to bottom, rgba(245,241,239,{a}) 0%, rgba(245,241,239,{a*.75}) 55%, rgba(245,241,239,0) 100%)"

POSTS = {
    # 1. Brand manifesto
    "p1": dict(plate="a1-water-pouring", file="01-help-me-do-it-myself", pos="50% 70%",
        scrim=CREAM_FADE(640, .55), html="""
<div class="eyebrow eyebrow-tr">The Montessori way</div>
<div style="position:absolute;left:64px;right:64px;top:250px">
  <h1 style="font-size:104px">Help me<br>to do it <em>myself.</em></h1>
  <div style="display:flex;align-items:center;gap:18px;margin-top:34px">
    <div class="rule"></div><div class="eyebrow" style="letter-spacing:.2em">Dr Maria Montessori</div></div>
  <p class="body" style="margin-top:26px;max-width:560px">Real glass. Real water. Real trust.<br>That is where independence begins.</p>
</div>"""),

    # 2. Funded hours (England)
    "p2": dict(plate="a2-first-shoes", file="02-funded-hours-from-9-months", pos="50% 80%",
        scrim=CREAM_FADE(820, .7), html="""
<div class="eyebrow eyebrow-tr">Funded childcare<br>England</div>
<div style="position:absolute;left:64px;right:64px;top:240px">
  <h1 style="font-size:96px">30 funded hours<br>a week, <em>from<br>9 months.</em></h1>
  <p class="body" style="margin-top:32px;max-width:640px">For eligible working parents, from the term after your child turns 9&nbsp;months, until they start school. We will help you work out exactly what it means for your family.</p>
  <div style="margin-top:34px" class="pill">Ask us about funding <b>0204 618 3477</b></div>
  <p class="fine" style="margin-top:22px;max-width:640px">Up to 38 weeks a year. Eligibility criteria apply; check yours on GOV.UK. Meals, consumables and additional hours may be charged. Hammersmith welcomes children from 12&nbsp;months.</p>
</div>"""),

    # 3. Key person observation
    "p3": dict(plate="a3-bead-threading", file="03-what-we-noticed-today", pos="50% 50%",
        scrim=CREAM_FADE(430, .82), html="""
<div class="eyebrow eyebrow-tr">From the key person's<br>notebook</div>
<div style="position:absolute;left:64px;top:232px"><h1 style="font-size:88px">What we <em>noticed</em><br>today.</h1></div>
<div style="position:absolute;left:78px;top:500px;width:470px;transform:rotate(-2.2deg);background:#fbf8f1;
     padding:34px 36px 30px;box-shadow:0 18px 40px rgba(30,36,30,.22),0 2px 6px rgba(30,36,30,.12);
     background-image:repeating-linear-gradient(to bottom, transparent 0 43px, rgba(95,122,90,.18) 43px 44px);
     background-position:0 92px">
  <div class="eyebrow" style="font-size:12px;letter-spacing:.22em;color:#8a6f52">Observation &middot; Tues 11:42</div>
  <p style="font-family:Caveat;font-size:36px;line-height:44px;color:#2e3b4f;margin-top:16px">Threaded 23 beads. Dropped one, found it under the mat, carried on. Didn&#39;t ask for help once.</p>
  <p style="font-family:Caveat;font-size:30px;line-height:44px;color:#5f7a5a;margin-top:6px"><u>Next step:</u> a longer lace and a colour pattern to follow.</p>
</div>
<p class="body" style="position:absolute;left:64px;bottom:130px;max-width:440px;color:#fff;font-size:20px;text-shadow:0 1px 12px rgba(0,0,0,.35)">Every child has a key person who watches closely, writes it down and plans what comes next.</p>"""),

    # 4. Autumn nature table
    "p4": dict(plate="a4-autumn-nature-table", file="04-autumn-at-child-height", pos="50% 70%",
        scrim=CREAM_FADE(620, .6), html="""
<div class="eyebrow eyebrow-tr">This week on<br>the nature table</div>
<div style="position:absolute;left:64px;right:64px;top:250px">
  <h1 style="font-size:104px">Autumn, at<br><em>child height.</em></h1>
  <p class="body" style="margin-top:30px;max-width:600px">Conkers, sycamore keys and a great many questions. Why is it shiny? Where did it come from? Will it grow?</p>
</div>"""),

    # 5. Careers
    "p5": dict(plate="b1-practitioner-presenting", file="05-careers-teach-slowly", pos="50% 70%",
        scrim=CREAM_FADE(640, .62), html="""
<div class="eyebrow eyebrow-tr">We're hiring<br>West London</div>
<div style="position:absolute;left:64px;right:64px;top:250px">
  <h1 style="font-size:100px">Teach <em>slowly.</em><br>Notice everything.</h1>
  <p class="body" style="margin-top:30px;max-width:640px">Montessori practitioners and early years educators for Hounslow, Heston and Hammersmith. Real training, genuine support, and a path from apprentice to manager.</p>
  <div style="margin-top:32px" class="pill">Apply at <b>alexandramontessori.co.uk/careers</b></div>
</div>"""),

    # 6. Ofsted / belonging
    "p6": dict(plate="b2-coat-pegs", file="06-somewhere-they-belong-ofsted-good", pos="50% 70%",
        scrim=CREAM_FADE(620, .55), html="""
<div class="eyebrow eyebrow-tr">Hounslow<br>Hammersmith</div>
<div style="position:absolute;left:64px;right:64px;top:250px">
  <h1 style="font-size:104px">Somewhere<br>they <em>belong.</em></h1>
  <div style="display:flex;align-items:center;gap:18px;margin-top:36px">
    <div class="rule"></div><div class="eyebrow" style="font-size:17px;color:var(--laurel)">Rated Good by Ofsted</div></div>
  <p class="body" style="margin-top:20px;max-width:600px">Our Hounslow and Hammersmith nurseries are both rated Good. Read the full reports on the Ofsted website.</p>
</div>"""),

    # 7. Practical life - snack
    "p7": dict(plate="b3-snack-table", file="07-they-set-the-table", pos="50% 70%",
        scrim=CREAM_FADE(620, .62), html="""
<div class="eyebrow eyebrow-tr">Practical life</div>
<div style="position:absolute;left:64px;right:64px;top:250px">
  <h1 style="font-size:98px">They <em>set</em> the table.<br>Then they sit at it.</h1>
  <p class="body" style="margin-top:30px;max-width:620px">Real plates, real glasses, real responsibility. Small jobs that build very big confidence.</p>
</div>"""),

    # 8. Book a visit
    "p8": dict(plate="b4-pink-tower-light", file="08-book-a-visit", pos="60% 60%",
        scrim=CREAM_FADE(760, .5), html="""
<div class="eyebrow eyebrow-tr">Book a visit</div>
<div style="position:absolute;left:64px;right:64px;top:250px">
  <h1 style="font-size:96px">Come and see us<br>on an <em>ordinary</em><br>morning.</h1>
  <p class="body" style="margin-top:30px;max-width:560px">No open-day polish. Just the hum, the focus and the snack table. Then ask us anything.</p>
</div>
<div style="position:absolute;left:64px;bottom:128px;display:flex;gap:38px;color:#fff">
  <div><div class="eyebrow" style="color:#dfe9d9;font-size:12px">Hounslow</div><div style="font-size:21px;font-weight:500;margin-top:6px">0208 001 5165</div></div>
  <div><div class="eyebrow" style="color:#dfe9d9;font-size:12px">Heston</div><div style="font-size:21px;font-weight:500;margin-top:6px">0203 627 6707</div></div>
  <div><div class="eyebrow" style="color:#dfe9d9;font-size:12px">Hammersmith</div><div style="font-size:21px;font-weight:500;margin-top:6px">0204 618 3477</div></div>
</div>"""),
}


def render(key, post):
    src = PLATES / f"{post['plate']}.png"
    if not src.exists():
        print(f"  skip {key}: no plate {src.name}")
        return
    GRADED.mkdir(exist_ok=True); HTML.mkdir(exist_ok=True); OUT.mkdir(exist_ok=True)
    g = GRADED / f"{post['plate']}.jpg"
    if not g.exists() or g.stat().st_mtime < src.stat().st_mtime:
        grade(src, g)
    h = HTML / f"{post['file']}.html"
    h.write_text(page(g.name, post["html"], post["pos"], post.get("scrim")), encoding="utf-8")
    big = OUT / f"_{post['file']}@2x.png"
    subprocess.run([CHROME, "--headless=new", "--disable-gpu", "--hide-scrollbars", "--allow-file-access-from-files",
                    f"--window-size={W},{H}", "--force-device-scale-factor=2", "--virtual-time-budget=4000",
                    f"--screenshot={big}", h.as_uri()], check=True, capture_output=True)
    Image.open(big).convert("RGB").resize((W, H), Image.LANCZOS).save(OUT / f"{post['file']}.png", optimize=True)
    big.unlink()
    print(f"  ok {key} -> final/{post['file']}.png")


if __name__ == "__main__":
    keys = sys.argv[1:] or list(POSTS)
    for k in keys:
        render(k, POSTS[k])
