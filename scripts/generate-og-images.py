#!/usr/bin/env python3
"""Generate 1200x630 OG images for Phel site and blog posts.

Usage:
    python3 scripts/generate-og-images.py
"""
from __future__ import annotations

import os
from pathlib import Path

from PIL import Image, ImageDraw, ImageFont

ROOT = Path(__file__).resolve().parent.parent
OUT_DIR = ROOT / "static" / "images"
OUT_DIR.mkdir(parents=True, exist_ok=True)

WIDTH, HEIGHT = 1200, 630
BG = (17, 24, 39)         # slate-900
ACCENT = (129, 140, 248)  # indigo-400
FG = (248, 250, 252)      # slate-50
MUTED = (148, 163, 184)   # slate-400
BRAND = (81, 45, 168)     # #512da8


def load_font(size: int, bold: bool = False) -> ImageFont.FreeTypeFont:
    candidates_regular = [
        "/System/Library/Fonts/Supplemental/Arial.ttf",
        "/System/Library/Fonts/Helvetica.ttc",
        "/Library/Fonts/Arial.ttf",
    ]
    candidates_bold = [
        "/System/Library/Fonts/Supplemental/Arial Bold.ttf",
        "/System/Library/Fonts/HelveticaNeue.ttc",
        "/Library/Fonts/Arial Bold.ttf",
    ]
    for path in (candidates_bold if bold else candidates_regular):
        if os.path.exists(path):
            return ImageFont.truetype(path, size=size)
    return ImageFont.load_default()


def wrap(draw: ImageDraw.ImageDraw, text: str, font: ImageFont.FreeTypeFont, max_w: int) -> list[str]:
    words = text.split()
    lines: list[str] = []
    current = ""
    for w in words:
        trial = f"{current} {w}".strip()
        bbox = draw.textbbox((0, 0), trial, font=font)
        if bbox[2] - bbox[0] <= max_w:
            current = trial
        else:
            if current:
                lines.append(current)
            current = w
    if current:
        lines.append(current)
    return lines


def draw_logo(draw: ImageDraw.ImageDraw, cx: int, cy: int, size: int) -> None:
    r = size // 2
    draw.ellipse((cx - r, cy - r, cx + r, cy + r), fill=BRAND)
    font = load_font(size=int(size * 0.55), bold=True)
    bbox = draw.textbbox((0, 0), "P", font=font)
    tw, th = bbox[2] - bbox[0], bbox[3] - bbox[1]
    draw.text((cx - tw / 2 - bbox[0], cy - th / 2 - bbox[1]), "P", fill=FG, font=font)


def render(title: str, subtitle: str, out_path: Path) -> None:
    img = Image.new("RGB", (WIDTH, HEIGHT), BG)
    draw = ImageDraw.Draw(img)

    # Accent bar bottom
    draw.rectangle((0, HEIGHT - 12, WIDTH, HEIGHT), fill=ACCENT)

    # Logo
    draw_logo(draw, cx=110, cy=110, size=120)

    # Brand name next to logo
    brand_font = load_font(size=44, bold=True)
    draw.text((195, 78), "Phel", fill=FG, font=brand_font)

    muted_font = load_font(size=24)
    draw.text((195, 132), "phel-lang.org", fill=MUTED, font=muted_font)

    # Title
    title_font = load_font(size=76, bold=True)
    max_w = WIDTH - 160
    lines = wrap(draw, title, title_font, max_w)[:3]
    y = 260
    for line in lines:
        draw.text((80, y), line, fill=FG, font=title_font)
        y += 92

    # Subtitle
    if subtitle:
        sub_font = load_font(size=30)
        sub_lines = wrap(draw, subtitle, sub_font, max_w)[:2]
        y = max(y + 12, HEIGHT - 140)
        for line in sub_lines:
            draw.text((80, y), line, fill=MUTED, font=sub_font)
            y += 42

    img.save(out_path, "PNG", optimize=True)
    print(f"wrote {out_path}")


def main() -> None:
    render(
        title="Phel",
        subtitle="A functional Lisp that transpiles to PHP. Immutable data, macros, REPL.",
        out_path=OUT_DIR / "og-default.png",
    )
    render(
        title="Destructuring Deep Dive",
        subtitle="Nested vectors and maps, :keys, :or, :as, & rest — the Clojure way in Phel.",
        out_path=OUT_DIR / "og-destructuring-deep-dive.png",
    )


if __name__ == "__main__":
    main()
