"""Finish Codex posters: drop the real Alexandra logo into the empty white circle the
poster reserved, then export Instagram 4:5 at 1080x1350 into final_posters/.

Usage: python finish.py            # every full/*.png (latest -vN wins per poster)
       python finish.py p1 r4      # only these prefixes
"""
import os
import sys
from pathlib import Path

import numpy as np
from PIL import Image, ImageDraw
from scipy import ndimage

ROOT = Path(__file__).resolve().parent
SET = os.environ.get("POSTER_SET", "")  # e.g. "v3" -> full_v3/ -> final_v3/
SRC = ROOT / (f"full_{SET}" if SET else "full")
OUT = ROOT / (f"final_{SET}" if SET else "final_posters")
BADGE = Image.open((ROOT / "../../../src/assets/logo-badge.webp").resolve()).convert("RGBA")


def find_circle(im):
    """Largest near-white blob in the top 25% of the poster -> (cx, cy, r)."""
    a = np.asarray(im.convert("RGB")).astype(int)
    h, w, _ = a.shape
    top = a[: int(h * 0.25)]
    mask = (top.min(axis=2) > 246) & ((top.max(axis=2) - top.min(axis=2)) < 8)
    lab, n = ndimage.label(mask)
    if not n:
        return None
    sizes = ndimage.sum(mask, lab, range(1, n + 1))
    k = int(np.argmax(sizes)) + 1
    ys, xs = np.nonzero(lab == k)
    x0, x1, y0, y1 = xs.min(), xs.max(), ys.min(), ys.max()
    d = max(x1 - x0, y1 - y0)
    if d < w * 0.08 or abs((x1 - x0) - (y1 - y0)) > d * 0.15:  # not a circle-ish blob
        return None
    return (x0 + x1) / 2, (y0 + y1) / 2, d / 2


def finish(path):
    im = Image.open(path).convert("RGBA")
    c = find_circle(im)
    if c:
        cx, cy, r = c
        d = int(round(r * 2 * 1.02))
        badge = BADGE.resize((d, d), Image.LANCZOS)
        # clip to a circle so the badge edge sits cleanly on the reserved disc
        m = Image.new("L", (d * 4, d * 4), 0)
        ImageDraw.Draw(m).ellipse((0, 0, d * 4 - 1, d * 4 - 1), fill=255)
        badge.putalpha(Image.fromarray(np.minimum(np.asarray(badge.getchannel("A")), np.asarray(m.resize((d, d), Image.LANCZOS)))))
        im.alpha_composite(badge, (int(round(cx - d / 2)), int(round(cy - d / 2))))
    else:
        print(f"  !! no logo circle found in {path.name}")
    out = im.convert("RGB").resize((1080, 1350), Image.LANCZOS)
    OUT.mkdir(exist_ok=True)
    name = path.stem.split("-v")[0] if "-v" in path.stem[-3:] else path.stem
    out.save(OUT / f"{name}.png", optimize=True)
    print(f"  ok {path.name} -> final_posters/{name}.png" + (f"  (logo r={c[2]:.0f})" if c else ""))


if __name__ == "__main__":
    want = sys.argv[1:]
    latest = {}
    for p in sorted(SRC.glob("[prv]*.png"), key=lambda p: p.stat().st_mtime):
        key = p.stem.split("-")[0]
        latest[key] = p
    for key, p in sorted(latest.items()):
        if not want or key in want:
            finish(p)
