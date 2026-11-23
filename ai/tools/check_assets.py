#!/usr/bin/env python3
"""Check delivered SPMB visual assets against their briefs.

Usage (from the repository root):
    python3 ai/tools/check_assets.py            # every brief with status "generated"
    python3 ai/tools/check_assets.py 015 019    # only these brief IDs, whatever their status
    python3 ai/tools/check_assets.py --all      # every brief that is not "done"

Needs Python 3.9+ and Pillow (pip install pillow) for the WebP/JPEG checks.
Exit code 1 when any check fails.
"""

from __future__ import annotations

import re
import sys
import xml.etree.ElementTree as ET
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
AI = ROOT / "ai"
GEN = AI / "generated"
INSTALL = GEN / "install"

# Longest side (px) and budget (KB) of installed paintings; see ai/style-guide.md → Sizes and budgets.
AREAS = {
    "hero": (1100, 220),
    "section": (800, 140),
    "guide": (1000, 180),
    "state": (480, 70),
    "share": (1200, 180),
    "ikon": (512, 60),
    "langkah": (640, 90),
}
# From this brief on, new paintings read on light and dark pages and get no -dark copies (D-083).
FIRST_WITHOUT_DARK = 36
SVG_BUDGET_KB = {"pola": 4, "pemisah": 3, "tombol": 6, "memuat": 4}
SVG_DEFAULT_KB = 6
SUFFIX = {"light": "", "dark": "-dark", "hijau": "-hijau", "dark-hijau": "-dark-hijau"}
FORBIDDEN_SVG_TAGS = {"text", "script", "foreignObject", "image", "style-import", "font", "tspan", "textPath"}

failures: list[str] = []
passes = 0


def fail(brief: str, msg: str) -> None:
    failures.append(f"FAIL {brief}: {msg}")


def ok() -> None:
    global passes
    passes += 1


def front_matter(path: Path) -> dict[str, str]:
    text = path.read_text(encoding="utf-8")
    m = re.match(r"---\n(.*?)\n---\n", text, re.S)
    data: dict[str, str] = {}
    if not m:
        return data
    for line in m.group(1).splitlines():
        line = line.split("#", 1)[0].rstrip()
        if ":" in line:
            key, value = line.split(":", 1)
            data[key.strip()] = value.strip()
    return data


def check_raster(brief: str, path: Path, width: int | None, budget_kb: int, alpha: bool, height: int | None = None, longest: int | None = None) -> tuple[int, int] | None:
    if not path.is_file():
        fail(brief, f"missing {path.relative_to(ROOT)}")
        return None
    try:
        from PIL import Image
    except ImportError:
        fail(brief, "Pillow is not installed (pip install pillow)")
        return None
    with Image.open(path) as img:
        rel = path.relative_to(ROOT)
        if width and img.width != width:
            fail(brief, f"{rel} is {img.width} px wide, expected {width}")
        if height and img.height != height:
            fail(brief, f"{rel} is {img.height} px high, expected {height}")
        if longest and max(img.size) > longest:
            fail(brief, f"{rel} is {img.width}×{img.height}, longest side must be ≤ {longest}")
        size = img.size
        if alpha:
            if "A" not in img.getbands():
                fail(brief, f"{rel} has no alpha channel (transparent background expected)")
            else:
                a = img.getchannel("A")
                corners = [a.getpixel((0, 0)), a.getpixel((img.width - 1, 0)), a.getpixel((0, img.height - 1)), a.getpixel((img.width - 1, img.height - 1))]
                if max(corners) > 8:
                    fail(brief, f"{rel} corners are not transparent (painted background or checkerboard?)")
    kb = path.stat().st_size / 1024
    if kb > budget_kb:
        fail(brief, f"{path.relative_to(ROOT)} is {kb:.0f} KB, budget {budget_kb} KB")
    ok()
    return size


def check_painting(brief: str, fm: dict[str, str]) -> None:
    name, area = fm.get("name", ""), fm.get("area", "")
    if area not in AREAS:
        fail(brief, f"unknown area '{area}'")
        return
    limit, budget = AREAS[area]
    sizes: dict[str, tuple[int, int]] = {}
    transparent = fm.get("background", "transparent") == "transparent"
    variants = [v.strip() for v in fm.get("variants", "light").split(",") if v.strip()]
    if brief.isdigit() and int(brief) >= FIRST_WITHOUT_DARK:
        if any(v.startswith("dark") for v in variants):
            fail(brief, "new paintings have no dark variant (D-083); use 'variants: light, hijau'")
            variants = [v for v in variants if not v.startswith("dark")]
        if not transparent:
            fail(brief, "new paintings are delivered on real transparency (D-083)")
    for v in variants:
        if v not in SUFFIX:
            fail(brief, f"unknown variant '{v}'")
            continue
        file = name + SUFFIX[v]
        need_alpha = transparent or v.startswith("dark")
        master = GEN / f"{file}.webp"
        if not master.is_file():
            fail(brief, f"missing master {master.relative_to(ROOT)}")
        if area == "share":
            check_raster(brief, INSTALL / "img" / area / f"{file}.webp", 1200, budget, False, 630)
            check_raster(brief, INSTALL / "img" / area / f"{file}.jpg", 1200, budget, False, 630)
        else:
            size = check_raster(brief, INSTALL / "img" / area / f"{file}.webp", None, budget, need_alpha, longest=limit)
            if size:
                sizes[v] = size
    if len(set(sizes.values())) > 1:
        fail(brief, f"variants differ in size {sizes}; use one crop box for all variants")


def check_svg(brief: str, path: Path) -> None:
    rel = path.relative_to(ROOT)
    raw = path.read_text(encoding="utf-8")
    try:
        root = ET.fromstring(raw)
    except ET.ParseError as e:
        fail(brief, f"{rel} is not valid XML: {e}")
        return
    if root.get("viewBox") is None:
        fail(brief, f"{rel} has no viewBox")
    for el in root.iter():
        tag = el.tag.split("}", 1)[-1]
        if tag in FORBIDDEN_SVG_TAGS:
            fail(brief, f"{rel} contains <{tag}>")
        for key, value in el.attrib.items():
            k = key.split("}", 1)[-1]
            if k.lower().startswith("on"):
                fail(brief, f"{rel} has an event handler '{k}'")
            if k == "href" and not value.startswith("#"):
                fail(brief, f"{rel} references an external resource '{value}'")
    if re.search(r"@import|url\((?!#)", raw):
        fail(brief, f"{rel} loads an external resource")
    is_mask = path.parent.name in {"pola", "pemisah", "tombol"} or path.stem == "memuat-kecil"
    if is_mask:
        colours = set(re.findall(r"#[0-9a-fA-F]{3,6}\b", raw)) - {"#000", "#000000"}
        if colours or "currentColor" in raw:
            fail(brief, f"{rel} is a mask and may only use #000 (found {', '.join(sorted(colours)) or 'currentColor'})")
    kind = path.parent.name
    budget = SVG_BUDGET_KB.get(kind, SVG_DEFAULT_KB)
    kb = path.stat().st_size / 1024
    if kb > budget:
        fail(brief, f"{rel} is {kb:.1f} KB, budget {budget} KB")
    if "@keyframes" in raw or "<animate" in raw:
        if "prefers-reduced-motion" not in raw:
            fail(brief, f"{rel} animates without a prefers-reduced-motion rule")
    ok()


def check_css(brief: str, path: Path) -> None:
    rel = path.relative_to(ROOT)
    css = path.read_text(encoding="utf-8")
    body = re.sub(r"/\*.*?\*/", "", css, flags=re.S)
    if "@layer components" not in body:
        fail(brief, f"{rel} is not wrapped in @layer components")
    if "@apply" in body:
        fail(brief, f"{rel} uses @apply (plain CSS only)")
    if re.search(r"text-transform\s*:\s*uppercase", body):
        fail(brief, f"{rel} uses uppercase text (no eyebrows, D-070)")
    if re.search(r"border-(top|left|right|inline-start|inline-end)(-width)?\s*:\s*([2-9]|\d{2,})px", body):
        fail(brief, f"{rel} has a thick top/side border (no accent borders, D-070)")
    if re.search(r"(animation|transition)\s*:", body) and "prefers-reduced-motion" not in body:
        fail(brief, f"{rel} animates without a prefers-reduced-motion rule")
    if re.search(r"mask(-image)?\s*:", body) and "-webkit-mask" not in body:
        fail(brief, f"{rel} uses mask without the -webkit-mask fallback")
    if re.search(r"https?://|@import", body):
        fail(brief, f"{rel} loads an external resource")
    for colour in re.findall(r"#(?:2563eb|005c2f)\b", body, re.I):
        fail(brief, f"{rel} hard-codes a brand colour {colour}; use var(--color-primary-*)")
    ok()


def check_svg_brief(brief: str, fm: dict[str, str]) -> None:
    files = [f.strip() for f in fm.get("files", "").split(",") if f.strip()]
    if not files:
        fail(brief, "brief lists no files")
    for f in files:
        path = INSTALL / f
        if not path.is_file():
            fail(brief, f"missing {path.relative_to(ROOT)}")
        elif path.suffix == ".svg":
            check_svg(brief, path)
        elif path.suffix == ".css":
            check_css(brief, path)
    if not (GEN / "demo" / "index.html").is_file():
        fail(brief, "missing ai/generated/demo/index.html")


def main(argv: list[str]) -> int:
    ids = [a for a in argv if a.isdigit()]
    every = "--all" in argv
    briefs = sorted((AI / "prompts").glob("[0-9][0-9][0-9]-*.md"))
    checked = 0
    for path in briefs:
        fm = front_matter(path)
        bid = fm.get("id", path.name[:3])
        status = fm.get("status", "")
        if ids and bid not in ids:
            continue
        if not ids and not (status == "generated" or (every and status != "done")):
            continue
        kind = fm.get("kind", "painting")
        checked += 1
        if kind == "painting":
            check_painting(bid, fm)
        elif kind == "svg":
            check_svg_brief(bid, fm)
        else:
            print(f"skip {bid}: kind '{kind}' is checked by hand")
    for line in failures:
        print(line)
    print(f"{checked} brief(s) checked, {passes} file(s) passed, {len(failures)} failure(s)")
    return 1 if failures else 0


if __name__ == "__main__":
    sys.exit(main(sys.argv[1:]))
