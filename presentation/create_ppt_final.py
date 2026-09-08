#!/usr/bin/env python3
"""First-PPT structure + real photos + solid/half-color layouts + numbered citations."""

from pptx import Presentation
from pptx.util import Inches, Pt, Emu
from pptx.dml.color import RGBColor
from pptx.enum.text import PP_ALIGN
from pptx.enum.shapes import MSO_SHAPE
import os

BASE = os.path.dirname(os.path.abspath(__file__))
ASSETS = os.path.join(BASE, "assets")
MEDIA = os.path.join(ASSETS, "slide_media")
OUT = os.path.join(BASE, "Arsalan_Firdous_AI_IoT_Paddy_Irrigation_UNNATI.pptx")

W = Inches(13.333)
H = Inches(7.5)

# Palette
TEAL = RGBColor(0x00, 0x6B, 0x7A)
TEAL_DARK = RGBColor(0x0A, 0x3D, 0x4A)
CYAN = RGBColor(0x1A, 0xA8, 0xB8)
WHITE = RGBColor(0xFF, 0xFF, 0xFF)
NAVY = RGBColor(0x0D, 0x2B, 0x3A)
GRAY = RGBColor(0x33, 0x3F, 0x48)
MUTED = RGBColor(0x5A, 0x6A, 0x72)
GREEN = RGBColor(0x1A, 0x7A, 0x5C)
ORANGE = RGBColor(0xC4, 0x6B, 0x2E)
BLUE = RGBColor(0x1E, 0x5F, 0x8A)
LIGHT = RGBColor(0xEE, 0xF7, 0xF8)
CARD = RGBColor(0xF7, 0xFB, 0xFC)

TOTAL = 15


def set_run(run, size=18, bold=False, color=NAVY, font="Calibri"):
    run.font.size = Pt(size)
    run.font.bold = bold
    run.font.color.rgb = color
    run.font.name = font


def media(name):
    return os.path.join(MEDIA, name)


def logo(name):
    return os.path.join(ASSETS, name)


def add_solid_bg(slide, color):
    shape = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, 0, 0, W, H)
    shape.fill.solid()
    shape.fill.fore_color.rgb = color
    shape.line.fill.background()
    return shape


def add_half_bg(slide, left_color, right_photo=None, left_ratio=0.52):
    """Solid color on left, photo on right (or reverse if left_ratio < 0.5)."""
    left_w = Inches(13.333 * left_ratio)
    right_w = W - left_w
    left = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, 0, 0, left_w, H)
    left.fill.solid()
    left.fill.fore_color.rgb = left_color
    left.line.fill.background()
    if right_photo and os.path.exists(right_photo):
        slide.shapes.add_picture(right_photo, left_w, 0, width=right_w, height=H)
    else:
        right = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, left_w, 0, right_w, H)
        right.fill.solid()
        right.fill.fore_color.rgb = TEAL
        right.line.fill.background()
    return left_w


def add_photo_right(slide, photo, left_ratio=0.55):
    left_w = Inches(13.333 * left_ratio)
    right_w = W - left_w
    if os.path.exists(photo):
        slide.shapes.add_picture(photo, left_w, 0, width=right_w, height=H)
    return left_w


def brand_header(slide, light=False):
    sku = logo("skuast_header.png") if os.path.exists(logo("skuast_header.png")) else logo("skuast_logo_card.png")
    unn = logo("unnati_header.png") if os.path.exists(logo("unnati_header.png")) else logo("unnati_official.png")
    if os.path.exists(sku):
        slide.shapes.add_picture(sku, Inches(0.28), Inches(0.12), height=Inches(0.52))
    if os.path.exists(unn):
        slide.shapes.add_picture(unn, Inches(9.55), Inches(0.12), height=Inches(0.52))


def add_footer(slide, page, dark=True):
    shape = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, 0, Inches(7.05), W, Inches(0.45))
    shape.fill.solid()
    shape.fill.fore_color.rgb = TEAL_DARK if dark else RGBColor(0xD8, 0xEC, 0xF0)
    shape.line.fill.background()
    p = shape.text_frame.paragraphs[0]
    p.alignment = PP_ALIGN.CENTER
    run = p.add_run()
    run.text = f"Arsalan Firdous  ·  SKUAST-Kashmir  ·  UNNATI AI 2.0 Roadshow  ·  {page}/{TOTAL}"
    set_run(run, 11, False, WHITE if dark else TEAL_DARK)


def add_title(slide, text, x=0.45, y=0.85, w=8.5, size=26, color=WHITE):
    box = slide.shapes.add_textbox(Inches(x), Inches(y), Inches(w), Inches(0.7))
    p = box.text_frame.paragraphs[0]
    run = p.add_run()
    run.text = text
    set_run(run, size, True, color)


def add_panel(slide, x, y, w, h, fill=WHITE):
    s = slide.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(x), Inches(y), Inches(w), Inches(h))
    s.fill.solid()
    s.fill.fore_color.rgb = fill
    s.line.color.rgb = RGBColor(0xC9, 0xDE, 0xE2)
    s.line.width = Pt(1)
    s.adjustments[0] = 0.08
    return s


def add_text(slide, x, y, w, h, lines, size=14, color=NAVY, bold=False, align=PP_ALIGN.LEFT, space=5):
    box = slide.shapes.add_textbox(Inches(x), Inches(y), Inches(w), Inches(h))
    tf = box.text_frame
    tf.word_wrap = True
    for i, line in enumerate(lines):
        p = tf.paragraphs[0] if i == 0 else tf.add_paragraph()
        p.alignment = align
        p.space_after = Pt(space)
        run = p.add_run()
        run.text = line
        set_run(run, size, bold, color)
    return box


def bullets(slide, x, y, w, h, items, size=13, color=GRAY):
    add_text(slide, x, y, w, h, [f"•  {t}" for t in items], size=size, color=color, space=6)


def add_photo_card(slide, photo, x, y, w, h):
    if not os.path.exists(photo):
        return
    # white frame
    frame = slide.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(x), Inches(y), Inches(w), Inches(h))
    frame.fill.solid()
    frame.fill.fore_color.rgb = WHITE
    frame.line.color.rgb = RGBColor(0xC9, 0xDE, 0xE2)
    frame.adjustments[0] = 0.06
    slide.shapes.add_picture(photo, Inches(x + 0.08), Inches(y + 0.08), width=Inches(w - 0.16), height=Inches(h - 0.16))


def build():
    # ensure headers exist
    from PIL import Image

    def make_card(src, dest, max_h=70, pad=12):
        if not os.path.exists(src):
            return
        im = Image.open(src).convert("RGBA")
        ratio = max_h / im.height
        im = im.resize((max(1, int(im.width * ratio)), max_h), Image.Resampling.LANCZOS)
        card = Image.new("RGBA", (im.width + pad * 2, im.height + pad * 2), (255, 255, 255, 250))
        card.paste(im, (pad, pad), im)
        card.save(dest)

    make_card(os.path.join(ASSETS, "skuast_logo.png"), os.path.join(ASSETS, "skuast_header.png"))
    unnati_src = os.path.join(ASSETS, "unnati_official.png")
    if not os.path.exists(unnati_src):
        unnati_src = os.path.join(ASSETS, "UnnatiAI.png")
    make_card(unnati_src, os.path.join(ASSETS, "unnati_header.png"))

    prs = Presentation()
    prs.slide_width = W
    prs.slide_height = H
    blank = prs.slide_layouts[6]

    # ===================== 1 TITLE =====================
    s = prs.slides.add_slide(blank)
    add_half_bg(s, TEAL_DARK, media("paddy_rows.jpg"), 0.55)
    brand_header(s)
    add_panel(s, 0.55, 1.55, 6.2, 4.6, fill=WHITE)
    add_text(s, 0.8, 1.8, 5.7, 0.35, ["UNNATI AI 2.0 Roadshow"], 13, CYAN, True)
    add_text(
        s, 0.8, 2.25, 5.7, 1.4,
        ["AI + IoT Integrated Paddy Irrigation\nDecision & Governance Platform"],
        22, TEAL_DARK, True,
    )
    add_text(
        s, 0.8, 3.85, 5.7, 1.8,
        [
            "Presented by: Arsalan Firdous",
            "SKUAST-Kashmir",
            "Sense → Analyze → Decide → Actuate → Govern",
            "Multimodal AI · IoT · Remote Sensing · PDN",
        ],
        14, GRAY,
    )
    add_footer(s, 1)

    # ===================== 2 AGENDA =====================
    s = prs.slides.add_slide(blank)
    add_solid_bg(s, RGBColor(0xE8, 0xF4, 0xF6))
    brand_header(s)
    add_title(s, "Presentation Roadmap", color=TEAL_DARK, w=12)
    items = [
        ("01", "Problem", "Irrigation loss, crop stress, soil & health gaps"),
        ("02", "Evidence", "Why AI + IoT and PDN outperform conventional methods [1–5]"),
        ("03", "Solution", "Governance platform with multimodal intelligence"),
        ("04", "Architecture", "Sensors, LoRaWAN, AI engine, PDN control, dashboards"),
        ("05", "How it works", "Field → village → governance levels + citations"),
    ]
    for i, (num, title, desc) in enumerate(items):
        y = 1.7 + i * 0.95
        add_panel(s, 0.7, y, 12.0, 0.82, fill=WHITE)
        circ = s.shapes.add_shape(MSO_SHAPE.OVAL, Inches(0.95), Inches(y + 0.14), Inches(0.52), Inches(0.52))
        circ.fill.solid()
        circ.fill.fore_color.rgb = TEAL
        circ.line.fill.background()
        cp = circ.text_frame.paragraphs[0]
        cp.alignment = PP_ALIGN.CENTER
        r = cp.add_run()
        r.text = num
        set_run(r, 12, True, WHITE)
        add_text(s, 1.7, y + 0.1, 10.5, 0.3, [title], 16, TEAL_DARK, True)
        add_text(s, 1.7, y + 0.42, 10.5, 0.3, [desc], 13, MUTED)
    add_footer(s, 2)

    # ===================== 3 PROBLEM + CITATIONS =====================
    s = prs.slides.add_slide(blank)
    add_half_bg(s, BLUE, media("canal_open.jpg"), 0.58)
    brand_header(s)
    add_title(s, "The Problem: Inefficient Irrigation Delivery", color=WHITE, w=7.2)
    stats = [
        ("~45%", "agricultural water may be lost in conveyance [1]"),
        ("30–40%", "overall canal project efficiency (often lower in practice) [2]"),
        ("60–83%", "applied paddy water may be lost to deep percolation [3]"),
        ("20–50%", "water can be saved with better irrigation scheduling vs flooding [4]"),
    ]
    for i, (val, lab) in enumerate(stats):
        y = 1.75 + i * 1.15
        add_panel(s, 0.45, y, 6.9, 1.0, fill=WHITE)
        add_text(s, 0.65, y + 0.12, 1.6, 0.7, [val], 22, TEAL, True)
        add_text(s, 2.4, y + 0.28, 4.7, 0.55, [lab], 13, GRAY)
    add_footer(s, 3)

    # ===================== 4 CROP / SOIL / HEALTH =====================
    s = prs.slides.add_slide(blank)
    add_half_bg(s, GREEN, media("paddy_flood.jpg"), 0.56)
    brand_header(s)
    add_title(s, "Crop, Soil & Field Health Challenges", color=WHITE, w=6.8)
    cards = [
        ("Soil & water", ["Moisture extremes", "Nutrient imbalance", "Waterlogging vs deficit", "No continuous root-zone data"]),
        ("Crop health", ["Stress not seen early", "Stage-mismatched irrigation", "Canopy decline missed", "Yield gaps accumulate"]),
        ("Pests / disease", ["Late visual detection", "Humidity-linked outbreaks", "No hotspot mapping", "Reactive chemical use"]),
    ]
    for i, (t, b) in enumerate(cards):
        y = 1.75 + i * 1.55
        add_panel(s, 0.45, y, 6.7, 1.4, fill=WHITE)
        add_text(s, 0.65, y + 0.12, 6.3, 0.3, [t], 15, TEAL_DARK, True)
        bullets(s, 0.65, y + 0.45, 6.3, 0.85, b, 12)
    add_footer(s, 4)

    # ===================== 5 WHY PDN vs CDN (lighter, with photo) =====================
    s = prs.slides.add_slide(blank)
    add_solid_bg(s, RGBColor(0xF2, 0xF7, 0xF8))
    brand_header(s)
    add_title(s, "Infrastructure Enabler: Why PDN Beats Open Canals", color=TEAL_DARK, w=12)
    add_panel(s, 0.45, 1.7, 5.7, 4.8, fill=WHITE)
    add_text(s, 0.7, 1.9, 5.2, 0.35, ["Open Canal (CDN)"], 16, ORANGE, True)
    bullets(
        s, 0.7, 2.4, 5.2, 3.8,
        [
            "Overall efficiency typically 30–40%, often 20–35% in practice [2]",
            "Seepage, evaporation, siltation, uneven head–tail delivery [1][2]",
            "Harder volumetric control per village / field",
            "Weak fit for uneven terrain without heavy earthwork [2]",
        ],
        13,
    )
    add_panel(s, 6.4, 1.7, 6.4, 4.8, fill=WHITE)
    add_text(s, 6.65, 1.9, 5.9, 0.35, ["Underground / Piped Network (PDN)"], 16, GREEN, True)
    bullets(
        s, 6.65, 2.4, 3.2, 3.8,
        [
            "Overall efficiency ~70–80% in PDN literature [2]",
            "Measurable flow at junctions and outlets",
            "Better for uneven terrain with boosters / PRVs [2]",
            "Creates a controllable layer for AI + IoT actuation",
        ],
        13,
    )
    add_photo_card(s, media("canal_pipe.jpg"), 10.0, 3.2, 2.55, 2.8)
    add_footer(s, 5)

    # ===================== 6 WHY AI + IoT =====================
    s = prs.slides.add_slide(blank)
    add_half_bg(s, TEAL_DARK, media("drone_multi.jpg"), 0.55)
    brand_header(s)
    add_title(s, "Why AI + IoT over Basin / Manual AWD Alone", color=WHITE, w=6.8)
    rows = [
        ("Basin flooding", "Simple, familiar", "High water use; weak timing [3][4]"),
        ("Manual AWD", "Saves ~20–50% vs flooding [4]", "Labour & observation limited"),
        ("IoT / automated AWD", "+13–20% extra saving vs manual; higher WUE [5]", "Still mostly field-local"),
        ("Our platform", "Sensors + imagery + weather + hydraulics + governance", "Demand-driven & auditable [5][6][7]"),
    ]
    add_panel(s, 0.4, 1.7, 6.7, 4.85, fill=WHITE)
    add_text(s, 0.6, 1.9, 1.9, 0.3, ["Approach"], 12, TEAL, True)
    add_text(s, 2.6, 1.9, 2.1, 0.3, ["Strength"], 12, TEAL, True)
    add_text(s, 4.8, 1.9, 2.1, 0.3, ["Gap"], 12, TEAL, True)
    for i, (a, b, c) in enumerate(rows):
        y = 2.4 + i * 0.95
        add_text(s, 0.6, y, 1.9, 0.8, [a], 12, TEAL_DARK, True)
        add_text(s, 2.6, y, 2.1, 0.8, [b], 12, GRAY)
        add_text(s, 4.8, y, 2.1, 0.8, [c], 12, MUTED)
    add_footer(s, 6)

    # ===================== 7 SOLUTION OVERVIEW =====================
    s = prs.slides.add_slide(blank)
    add_solid_bg(s, RGBColor(0xEAF, 0xF5, 0xF7) if False else RGBColor(0xEA, 0xF5, 0xF7))
    brand_header(s)
    add_title(s, "Our Solution: Smart Irrigation Control + Governance", color=TEAL_DARK, w=12)
    points = [
        (False, "Deploy soil-moisture & water-level sensors in representative fields"),
        (False, "Predict stage-specific water requirements using AI models"),
        (False, "IoT monitors depth, moisture, microclimate & pipe flow in real time"),
        (False, "Cloud AI forecasts demand 3–6 hours ahead [5]"),
        (True, "Automated valves at source, junctions & lateral outlets"),
        (True, "Cuts loss, prevents waterlogging, supports better yields [3][5]"),
        (True, "Farmer + authority dashboards with human override as fail-safe [7]"),
    ]
    for i, (green, t) in enumerate(points):
        y = 1.65 + i * 0.68
        add_panel(s, 0.7, y, 11.9, 0.58, fill=RGBColor(0xD8, 0xF0, 0xE6) if green else RGBColor(0xD6, 0xEE, 0xF2))
        add_text(s, 1.0, y + 0.12, 11.3, 0.35, [f"{i+1}.  {t}"], 14, GREEN if green else TEAL_DARK, True)
    add_footer(s, 7)

    # ===================== 8 SENSORS (REAL PHOTOS) =====================
    s = prs.slides.add_slide(blank)
    add_solid_bg(s, RGBColor(0xF4, 0xF8, 0xF9))
    brand_header(s)
    add_title(s, "IoT Sensing Layer: Field & Water Instruments", color=TEAL_DARK, w=12)
    add_photo_card(s, media("soil_sensor.jpg"), 0.45, 1.7, 4.0, 3.5)
    add_photo_card(s, media("water_sensor.jpg"), 4.65, 1.7, 4.0, 3.5)
    add_panel(s, 8.9, 1.7, 3.95, 4.85, fill=WHITE)
    add_text(s, 9.1, 1.95, 3.55, 0.35, ["What we measure"], 15, TEAL, True)
    bullets(
        s, 9.1, 2.45, 3.55, 3.8,
        [
            "Soil moisture, temp, EC / NPK (where feasible)",
            "Water depth / waterlogging risk",
            "pH, conductivity, turbidity at source / junctions",
            "Flow, pressure, valve state on PDN nodes",
            "LoRaWAN uplink to village gateway → cloud",
            "Solar + battery for autonomous nodes",
        ],
        12,
    )
    add_text(s, 0.5, 5.4, 4.0, 0.35, ["Soil probe sensor"], 12, MUTED, False, PP_ALIGN.CENTER)
    add_text(s, 4.7, 5.4, 4.0, 0.35, ["Water-quality sensor kit"], 12, MUTED, False, PP_ALIGN.CENTER)
    add_footer(s, 8)

    # ===================== 9 MULTISPECTRAL / SATELLITE =====================
    s = prs.slides.add_slide(blank)
    add_solid_bg(s, TEAL_DARK)
    brand_header(s)
    add_title(s, "Remote Sensing: Multispectral / Hyperspectral Intelligence", color=WHITE, w=12)
    add_photo_card(s, media("drone_multi.jpg"), 0.4, 1.65, 6.2, 3.7)
    add_photo_card(s, media("satellite_fields.jpg"), 6.85, 1.65, 6.0, 2.3)
    add_panel(s, 6.85, 4.15, 6.0, 2.35, fill=WHITE)
    add_text(s, 7.05, 4.3, 5.6, 0.3, ["What imagery adds [6]"], 14, TEAL, True)
    bullets(
        s, 7.05, 4.7, 5.6, 1.6,
        [
            "Vegetation vigor / NDVI-style health maps",
            "Water stress & waterlogging spatial patterns",
            "Disease / pest damage signatures before yield loss",
            "Coverage for fields without dense sensors",
        ],
        12,
    )
    add_text(s, 0.5, 5.5, 6.0, 0.35, ["UAV multispectral scan over crops"], 12, RGBColor(0xC8, 0xE8, 0xEE), False, PP_ALIGN.CENTER)
    add_footer(s, 9)

    # ===================== 10 WHOLE ARCHITECTURE =====================
    s = prs.slides.add_slide(blank)
    add_solid_bg(s, RGBColor(0xEAF, 0xF4, 0xF6) if False else RGBColor(0xEA, 0xF4, 0xF6))
    brand_header(s)
    add_title(s, "Whole Platform Architecture", color=TEAL_DARK, w=12)

    def arch_box(x, y, w, h, title, lines, fill):
        shape = s.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(x), Inches(y), Inches(w), Inches(h))
        shape.fill.solid()
        shape.fill.fore_color.rgb = fill
        shape.line.fill.background()
        shape.adjustments[0] = 0.08
        add_text(s, x + 0.1, y + 0.1, w - 0.2, 0.3, [title], 13, WHITE, True, PP_ALIGN.CENTER)
        add_text(s, x + 0.12, y + 0.42, w - 0.24, h - 0.5, lines, 11, WHITE, False, PP_ALIGN.CENTER, 2)

    arch_box(0.35, 1.7, 2.35, 2.0, "1. Sense", ["Soil / water sensors", "Flow · pressure", "Weather · ET0"], TEAL)
    arch_box(0.35, 4.0, 2.35, 2.0, "2. Imagery", ["Multispectral", "Hyperspectral", "Drone / satellite [6]"], CYAN)
    arch_box(3.0, 2.55, 2.45, 2.4, "3. Connect", ["LoRaWAN nodes", "Village gateway", "Solar · battery", "Edge safety rules"], GREEN)
    arch_box(5.75, 2.05, 2.55, 3.3, "4. AI Engine", ["Demand forecast", "Crop-health models", "Waterlogging risk", "Anomaly detection", "Priority scoring"], ORANGE)
    arch_box(8.6, 1.7, 2.2, 2.0, "5. Govern", ["Confidence score", "Approval paths", "Audit trail [7]"], TEAL_DARK)
    arch_box(8.6, 4.0, 2.2, 2.0, "6. Actuate", ["Valves · pumps", "Outlet schedule", "PDN control"], BLUE)
    arch_box(11.05, 2.55, 1.95, 2.4, "7. Users", ["Farmer app", "Operator UI", "Authority dashboard"], GREEN)
    add_text(s, 2.65, 3.45, 0.35, 0.3, ["→"], 18, TEAL_DARK, True, PP_ALIGN.CENTER)
    add_text(s, 5.4, 3.45, 0.35, 0.3, ["→"], 18, TEAL_DARK, True, PP_ALIGN.CENTER)
    add_text(s, 8.25, 3.45, 0.35, 0.3, ["→"], 18, TEAL_DARK, True, PP_ALIGN.CENTER)
    add_text(s, 10.75, 3.45, 0.3, 0.3, ["→"], 18, TEAL_DARK, True, PP_ALIGN.CENTER)
    add_footer(s, 10)

    # ===================== 11 PDN + CONTROL (highlight, not over-explain) =====================
    s = prs.slides.add_slide(blank)
    add_half_bg(s, BLUE, media("canal_pipe2.jpg"), 0.58)
    brand_header(s)
    add_title(s, "Control Layer: Source → Sub-main → Laterals", color=WHITE, w=7.2)
    blocks = [
        ("Source node", "Filters, flow/pressure/turbidity sensors, main actuator, solar + LoRa"),
        ("Village junction", "Sub-main branch, volumetric metering, valves, optional booster / PRV"),
        ("Field laterals", "Outlet sensors + actuators for section-wise release"),
        ("Reference fields", "Dense sensing for AI ground-truth; sparse sensing + imagery elsewhere"),
    ]
    for i, (t, d) in enumerate(blocks):
        y = 1.75 + i * 1.15
        add_panel(s, 0.4, y, 7.0, 1.0, fill=WHITE)
        add_text(s, 0.6, y + 0.12, 6.6, 0.3, [t], 14, TEAL_DARK, True)
        add_text(s, 0.6, y + 0.48, 6.6, 0.4, [d], 12, GRAY)
    add_footer(s, 11)

    # ===================== 12 METHODOLOGY LEVELS =====================
    s = prs.slides.add_slide(blank)
    add_solid_bg(s, RGBColor(0xF0, 0xF6, 0xF7))
    brand_header(s)
    add_title(s, "Methodology: Field → Cluster → Governance", color=TEAL_DARK, w=12)
    tiers = [
        (GREEN, "Level 1 — Field", "Sensors + outlet control + crop observations. AI predicts timing/duration from phenology, ET0, soil & weather."),
        (CYAN, "Level 2 — Village / Cluster", "Gateway aggregates data, forecasts 7–14 day demand, optimizes allocation, flags deficit / excess zones."),
        (ORANGE, "Level 3 — Authority", "Source monitoring, diversion recommendations, equity / leakage view, drought & emergency support with human oversight [7]."),
    ]
    for i, (color, title, body) in enumerate(tiers):
        y = 1.75 + i * 1.55
        bar = s.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(0.55), Inches(y), Inches(12.2), Inches(1.4))
        bar.fill.solid()
        bar.fill.fore_color.rgb = color
        bar.line.fill.background()
        bar.adjustments[0] = 0.08
        add_text(s, 0.85, y + 0.2, 11.6, 0.35, [title], 18, WHITE, True)
        add_text(s, 0.85, y + 0.65, 11.6, 0.55, [body], 13, WHITE)
    add_footer(s, 12)

    # ===================== 13 GOVERNANCE LOOP =====================
    s = prs.slides.add_slide(blank)
    add_half_bg(s, TEAL, media("farmers_transplant.jpg"), 0.55)
    brand_header(s)
    add_title(s, "Closed Loop with Human Supervision", color=WHITE, w=6.8)
    steps = [
        "1. Sense — field, network, weather, imagery",
        "2. Analyze — demand, stress, risk, anomalies",
        "3. Decide — recommendation + confidence + rules",
        "4. Actuate — valves / pumps only if safe",
        "5. Verify — sensors confirm outcome",
        "6. Govern — farmer / operator / authority override [7]",
    ]
    for i, t in enumerate(steps):
        y = 1.7 + i * 0.75
        add_panel(s, 0.4, y, 6.7, 0.65, fill=WHITE)
        add_text(s, 0.65, y + 0.15, 6.2, 0.35, [t], 13, TEAL_DARK, True)
    add_footer(s, 13)

    # ===================== 14 CITATIONS =====================
    s = prs.slides.add_slide(blank)
    add_solid_bg(s, RGBColor(0xF5, 0xF9, 0xFA))
    brand_header(s)
    add_title(s, "Numbered References (use [n] on data claims)", color=TEAL_DARK, w=12)
    add_panel(s, 0.45, 1.55, 12.4, 5.15, fill=WHITE)
    refs = [
        "[1] Hydrospatial / irrigation conveyance studies (e.g., Dudhganga canal modelling) & FAO Aquastat-aligned loss framing — ~45% conveyance loss in Indian agricultural water systems.",
        "[2] PDN vs CDN / piped irrigation literature & CWC-aligned guidance — CDN overall efficiency ~30–40% (often 20–35% actual); PDN ~70–80%; better for undulating terrain.",
        "[3] Rice water-use assessments (ICRISAT / Agricultural Research and related studies) — large share of irrigation water used by rice; 60–83% of applied water may be lost to deep percolation.",
        "[4] Bouman & Tuong (2001); Lampayan et al.; AWD field literature — alternate wetting and drying can save ~20–50% irrigation water vs continuous flooding.",
        "[5] IoT / automated AWD rice studies (e.g., Mekong Delta IoT AWD; Bangladesh automated AWD) — additional ~13–20% water savings vs manual AWD; higher water-use efficiency.",
        "[6] Multispectral / hyperspectral remote-sensing literature for vegetation indices, crop stress, waterlogging and disease / pest indicators in precision agriculture.",
        "[7] Precision irrigation & cyber-physical / human-in-the-loop governance framing — confidence-based automation with operator and authority override as fail-safe.",
    ]
    add_text(s, 0.7, 1.8, 11.9, 4.7, refs, 12, GRAY, space=8)
    add_footer(s, 14)

    # ===================== 15 THANK YOU =====================
    s = prs.slides.add_slide(blank)
    add_half_bg(s, TEAL_DARK, media("paddy_flood2.jpg"), 0.52)
    brand_header(s)
    add_panel(s, 0.55, 2.0, 5.9, 3.8, fill=WHITE)
    add_text(s, 0.8, 2.35, 5.4, 0.55, ["Thank You"], 32, TEAL_DARK, True, PP_ALIGN.CENTER)
    add_text(s, 0.8, 3.1, 5.4, 0.4, ["Questions & Discussion"], 18, TEAL, False, PP_ALIGN.CENTER)
    add_text(
        s, 0.8, 3.8, 5.4, 1.5,
        ["Arsalan Firdous", "SKUAST-Kashmir", "UNNATI AI 2.0 Roadshow"],
        14, GRAY, False, PP_ALIGN.CENTER,
    )
    add_footer(s, 15)

    prs.save(OUT)
    print("Saved:", OUT)


if __name__ == "__main__":
    build()
