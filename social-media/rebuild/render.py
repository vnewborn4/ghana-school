from __future__ import annotations

import argparse
import json
import subprocess
from pathlib import Path
from typing import Iterable

from PIL import Image, ImageDraw, ImageEnhance, ImageFilter, ImageFont


ROOT = Path(__file__).resolve().parents[2]
PROJECT = Path(__file__).resolve().parent
EXPORTS = ROOT / "social-media" / "exports-v2"
IMAGES = ROOT / "assets" / "images"

FONT_BOLD = Path(r"C:\Windows\Fonts\bahnschrift.ttf")
FONT_BODY = Path(r"C:\Windows\Fonts\segoeuib.ttf")

WHITE = "#FFFFFF"
CREAM = "#F8F3E8"
GOLD = "#E9B949"
INK = "#101815"


def font(path: Path, size: int) -> ImageFont.FreeTypeFont:
    return ImageFont.truetype(str(path), size=size)


def cover_crop(image: Image.Image, width: int, height: int, anchor: tuple[float, float]) -> Image.Image:
    image = image.convert("RGB")
    scale = max(width / image.width, height / image.height)
    resized = image.resize((round(image.width * scale), round(image.height * scale)), Image.Resampling.LANCZOS)
    ax, ay = anchor
    left = min(max(round(resized.width * ax - width / 2), 0), resized.width - width)
    top = min(max(round(resized.height * ay - height / 2), 0), resized.height - height)
    return resized.crop((left, top, left + width, top + height))


def grade(image: Image.Image) -> Image.Image:
    image = ImageEnhance.Contrast(image).enhance(1.06)
    image = ImageEnhance.Color(image).enhance(1.04)
    return ImageEnhance.Brightness(image).enhance(0.98)


def gradient_overlay(width: int, height: int, start: float = 0.42) -> Image.Image:
    layer = Image.new("RGBA", (width, height), (0, 0, 0, 0))
    px = layer.load()
    start_y = int(height * start)
    span = max(1, height - start_y)
    for y in range(start_y, height):
        p = (y - start_y) / span
        alpha = int(232 * (p ** 1.55))
        for x in range(width):
            px[x, y] = (8, 15, 12, alpha)
    return layer


def wrap(draw: ImageDraw.ImageDraw, text: str, typeface: ImageFont.FreeTypeFont, max_width: int) -> list[str]:
    words = text.split()
    lines: list[str] = []
    current = ""
    for word in words:
        trial = word if not current else f"{current} {word}"
        if draw.textbbox((0, 0), trial, font=typeface)[2] <= max_width:
            current = trial
        else:
            if current:
                lines.append(current)
            current = word
    if current:
        lines.append(current)
    return lines


def fit_headline(draw: ImageDraw.ImageDraw, text: str, max_width: int, max_lines: int, initial: int) -> tuple[ImageFont.FreeTypeFont, list[str]]:
    for size in range(initial, max(38, initial - 36), -2):
        typeface = font(FONT_BOLD, size)
        lines = wrap(draw, text, typeface, max_width)
        if len(lines) <= max_lines:
            return typeface, lines
    return typeface, lines


def draw_logo(canvas: Image.Image, x: int, y: int, size: int) -> None:
    logo = Image.open(IMAGES / "mill-creek-icon-transparent.png").convert("RGBA")
    logo.thumbnail((size, size), Image.Resampling.LANCZOS)
    pad = max(10, size // 9)
    plate = Image.new("RGBA", (logo.width + pad * 2, logo.height + pad * 2), (255, 255, 255, 232))
    plate.alpha_composite(logo, (pad, pad))
    mask = Image.new("L", plate.size, 0)
    ImageDraw.Draw(mask).rounded_rectangle((0, 0, plate.width - 1, plate.height - 1), radius=pad, fill=255)
    plate.putalpha(Image.composite(plate.getchannel("A"), Image.new("L", plate.size, 0), mask))
    canvas.alpha_composite(plate, (x, y))


def render_card(spec: dict) -> None:
    width, height = spec["size"]
    photo = grade(cover_crop(Image.open(IMAGES / spec["photo"]), width, height, tuple(spec["anchor"])))
    canvas = photo.convert("RGBA")
    canvas.alpha_composite(gradient_overlay(width, height, spec.get("gradient_start", 0.42)))
    draw = ImageDraw.Draw(canvas)

    margin = round(width * 0.066)
    top = round(height * 0.065)
    rail = max(8, round(width * 0.009))
    draw.rounded_rectangle((margin, top, margin + round(width * 0.17), top + rail), radius=rail // 2, fill=GOLD)
    draw_logo(canvas, margin, top + rail + round(width * 0.025), round(width * 0.10))

    tag_font = font(FONT_BODY, max(24, round(width * 0.026)))
    tag = "ACCRA  •  GHANA"
    tag_box = draw.textbbox((0, 0), tag, font=tag_font)
    tag_x = margin
    tag_y = round(height * spec.get("tag_y", 0.61))
    draw.text((tag_x, tag_y), tag, font=tag_font, fill=GOLD)

    headline_size = round(width * spec.get("headline_scale", 0.077))
    headline_font, lines = fit_headline(draw, spec["headline"].upper(), width - margin * 2, spec.get("max_lines", 3), headline_size)
    line_height = round(headline_font.size * 0.93)
    headline_y = tag_y + round(tag_font.size * 1.7)
    for i, line in enumerate(lines):
        draw.text((margin, headline_y + i * line_height), line, font=headline_font, fill=WHITE, stroke_width=max(1, width // 600), stroke_fill=INK)

    body_font = font(FONT_BODY, max(26, round(width * 0.03)))
    body_lines = wrap(draw, spec["subline"], body_font, width - margin * 2)
    body_y = headline_y + len(lines) * line_height + round(body_font.size * 0.65)
    for i, line in enumerate(body_lines[:2]):
        draw.text((margin, body_y + i * round(body_font.size * 1.25)), line, font=body_font, fill=CREAM)

    footer_font = font(FONT_BODY, max(22, round(width * 0.023)))
    footer = "millcreek-ar-learning.com"
    footer_y = height - round(height * 0.055) - footer_font.size
    draw.text((margin, footer_y), footer, font=footer_font, fill=WHITE)
    cta = "LEARN  •  GIVE  •  SHARE"
    cta_width = draw.textbbox((0, 0), cta, font=footer_font)[2]
    draw.text((width - margin - cta_width, footer_y), cta, font=footer_font, fill=GOLD)

    output = EXPORTS / spec["output"]
    output.parent.mkdir(parents=True, exist_ok=True)
    canvas.convert("RGB").save(output, quality=96, optimize=True)


STATIC_SPECS = [
    {
        "output": "image-square-1080.png",
        "size": (1080, 1080),
        "photo": "classroom-coding-close.jpeg",
        "anchor": (0.52, 0.43),
        "headline": "Talent is everywhere.",
        "subline": "Help expand technology learning at Mill Creek-AR Learning Center.",
        "tag_y": 0.58,
    },
    {
        "output": "image-portrait-1080x1350.png",
        "size": (1080, 1350),
        "photo": "classroom-group-portrait.jpeg",
        "anchor": (0.54, 0.47),
        "headline": "More time to learn. More room to grow.",
        "subline": "Support expanded classes for junior and senior high students.",
        "tag_y": 0.63,
        "headline_scale": 0.07,
    },
    {
        "output": "image-story-1080x1920.png",
        "size": (1080, 1920),
        "photo": "classroom-coding-wide.jpeg",
        "anchor": (0.50, 0.48),
        "headline": "Coding starts with access.",
        "subline": "Computers. Internet. Instruction. A place to practice.",
        "tag_y": 0.68,
    },
    {
        "output": "image-landscape-1200x628.png",
        "size": (1200, 628),
        "photo": "classroom-coding-wide.jpeg",
        "anchor": (0.49, 0.46),
        "headline": "Help expand the learning day.",
        "subline": "Technology learning at Mill Creek-AR Learning Center.",
        "tag_y": 0.46,
        "headline_scale": 0.062,
        "max_lines": 2,
    },
    {
        "output": "image-youtube-thumbnail-1280x720.png",
        "size": (1280, 720),
        "photo": "classroom-coding-close.jpeg",
        "anchor": (0.50, 0.43),
        "headline": "Inside a coding classroom",
        "subline": "Mill Creek-AR Learning Center",
        "tag_y": 0.48,
        "headline_scale": 0.067,
        "max_lines": 2,
    },
]


def main() -> None:
    parser = argparse.ArgumentParser(description="Render the corrected Mill Creek-AR social package.")
    parser.add_argument("--static-only", action="store_true", help="Render still graphics only.")
    args = parser.parse_args()

    for spec in STATIC_SPECS:
        render_card(spec)

    if not args.static_only:
        narration = PROJECT / "audio" / "narration.wav"
        if not narration.exists():
            raise SystemExit("Static graphics rendered. Video rendering is waiting for rebuild/audio/narration.wav.")

    print(f"Rendered corrected assets to {EXPORTS}")


if __name__ == "__main__":
    main()
