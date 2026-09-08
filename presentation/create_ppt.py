#!/usr/bin/env python3
"""UNNATI AI 2.0 Roadshow PPT — AI+IoT Paddy Irrigation Governance Platform"""

from pptx import Presentation
from pptx.util import Inches, Pt, Emu
from pptx.dml.color import RGBColor
from pptx.enum.text import PP_ALIGN, MSO_ANCHOR
from pptx.enum.shapes import MSO_SHAPE
from pptx.oxml.ns import nsmap
from pptx.oxml import parse_xml
from copy import deepcopy
import os

BASE = os.path.dirname(os.path.abspath(__file__))
ASSETS = os.path.join(BASE, "assets")
OUT = os.path.join(BASE, "Arsalan_Firdous_AI_IoT_Paddy_Irrigation_UNNATI.pptx")

# Colors
TEAL = RGBColor(0x00, 0x6B, 0x7A)
TEAL_DARK = RGBColor(0x0A, 0x3D, 0x4A)
CYAN = RGBColor(0x00, 0xA8, 0xB8)
WHITE = RGBColor(0xFF, 0xFF, 0xFF)
NAVY = RGBColor(0x0D, 0x2B, 0x3A)
GRAY = RGBColor(0x33, 0x3F, 0x48)
GREEN = RGBColor(0x1A, 0x7A, 0x5C)
ACCENT = RGBColor(0x00, 0x8C, 0x7A)
LIGHT = RGBColor(0xE8, 0xF7, 0xF8)
MUTED = RGBColor(0x5A, 0x6A, 0x72)

SLIDE_W = Inches(13.333)
SLIDE_H = Inches(7.5)


def set_run(run, size=18, bold=False, color=NAVY, font="Calibri"):
    run.font.size = Pt(size)
    run.font.bold = bold
    run.font.color.rgb = color
    run.font.name = font


def add_bg(slide, prs):
    path = os.path.join(ASSETS, "bg_slide.jpg")
    slide.shapes.add_picture(path, 0, 0, width=prs.slide_width, height=prs.slide_height)


def add_footer_bar(slide, prs, page, total=16):
    # Soft translucent footer strip
    shape = slide.shapes.add_shape(
        MSO_SHAPE.RECTANGLE, 0, Inches(7.05), prs.slide_width, Inches(0.45)
    )
    shape.fill.solid()
    shape.fill.fore_color.rgb = TEAL_DARK
    shape.line.fill.background()
    tf = shape.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.alignment = PP_ALIGN.CENTER
    run = p.add_run()
    run.text = f"Arsalan Firdous  ·  SKUAST-Kashmir  ·  UNNATI AI 2.0 Roadshow  ·  {page}/{total}"
    set_run(run, 11, False, WHITE)


def add_title(slide, text, left=Inches(0.5), top=Inches(0.28), width=Inches(12.3), size=32):
    box = slide.shapes.add_textbox(left, top, width, Inches(0.7))
    tf = box.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    run = p.add_run()
    run.text = text
    set_run(run, size, True, TEAL_DARK)
    return box


def add_panel(slide, left, top, width, height, fill=WHITE, alpha=None):
    shape = slide.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, left, top, width, height)
    shape.fill.solid()
    shape.fill.fore_color.rgb = fill
    shape.line.color.rgb = RGBColor(0xB8, 0xDC, 0xE0)
    shape.line.width = Pt(1)
    shape.adjustments[0] = 0.08
    return shape


def add_text_box(slide, left, top, width, height, lines, size=15, color=NAVY, bold_first=False, align=PP_ALIGN.LEFT):
    box = slide.shapes.add_textbox(left, top, width, height)
    tf = box.text_frame
    tf.word_wrap = True
    for i, line in enumerate(lines):
        p = tf.paragraphs[0] if i == 0 else tf.add_paragraph()
        p.alignment = align
        p.space_after = Pt(6)
        run = p.add_run()
        run.text = line
        set_run(run, size, bold_first and i == 0, color)
    return box


def bullet_panel(slide, left, top, width, height, title, bullets, title_color=TEAL, bullet_size=14):
    panel = add_panel(slide, left, top, width, height)
    # title
    tb = slide.shapes.add_textbox(left + Inches(0.18), top + Inches(0.12), width - Inches(0.35), Inches(0.4))
    tf = tb.text_frame
    p = tf.paragraphs[0]
    run = p.add_run()
    run.text = title
    set_run(run, 16, True, title_color)
    # bullets
    bb = slide.shapes.add_textbox(left + Inches(0.18), top + Inches(0.5), width - Inches(0.35), height - Inches(0.65))
    tf = bb.text_frame
    tf.word_wrap = True
    for i, b in enumerate(bullets):
        p = tf.paragraphs[0] if i == 0 else tf.add_paragraph()
        p.space_after = Pt(5)
        run = p.add_run()
        run.text = "•  " + b
        set_run(run, bullet_size, False, GRAY)
    return panel


def brand_header(slide, prs):
    """SKUAST left, Unnati right"""
    sku = os.path.join(ASSETS, "skuast_logo_card.png")
    unnati = os.path.join(ASSETS, "unnati_logo.png")
    badge = os.path.join(ASSETS, "skuast_badge.png")
    if os.path.exists(sku):
        slide.shapes.add_picture(sku, Inches(0.4), Inches(0.18), height=Inches(0.55))
    elif os.path.exists(badge):
        slide.shapes.add_picture(badge, Inches(0.35), Inches(0.15), height=Inches(0.6))
    if os.path.exists(unnati):
        slide.shapes.add_picture(unnati, Inches(10.2), Inches(0.12), height=Inches(0.65))


def make_prs():
    prs = Presentation()
    prs.slide_width = SLIDE_W
    prs.slide_height = SLIDE_H
    blank = prs.slide_layouts[6]
    total = 16

    # ========== 1. TITLE ==========
    s = prs.slides.add_slide(blank)
    add_bg(s, prs)
    brand_header(s, prs)

    # Center content card
    add_panel(s, Inches(1.8), Inches(1.5), Inches(9.7), Inches(4.6))
    add_text_box(
        s, Inches(2.1), Inches(1.7), Inches(9.1), Inches(0.45),
        ["UNNATI AI 2.0 Roadshow"], size=14, color=ACCENT, align=PP_ALIGN.CENTER
    )
    # Topic placeholder
    topic = s.shapes.add_textbox(Inches(2.1), Inches(2.15), Inches(9.1), Inches(1.5))
    tf = topic.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.alignment = PP_ALIGN.CENTER
    run = p.add_run()
    run.text = "[ TOPIC TITLE ]"
    set_run(run, 30, True, TEAL_DARK)
    p2 = tf.add_paragraph()
    p2.alignment = PP_ALIGN.CENTER
    run2 = p2.add_run()
    run2.text = "AI + IoT Integrated Paddy Irrigation\nDecision & Governance Platform"
    set_run(run2, 20, False, TEAL)

    add_text_box(
        s, Inches(2.1), Inches(4.0), Inches(9.1), Inches(1.5),
        [
            "Presented by: Arsalan Firdous",
            "Sher-e-Kashmir University of Agricultural Sciences & Technology (SKUAST)",
            "Cyber-Physical AI Governance for Intelligent Paddy Water Management",
        ],
        size=15, color=GRAY, align=PP_ALIGN.CENTER
    )
    add_footer_bar(s, prs, 1, total)

    # ========== 2. AGENDA ==========
    s = prs.slides.add_slide(blank)
    add_bg(s, prs)
    brand_header(s, prs)
    add_title(s, "Presentation Roadmap", top=Inches(0.95))
    items = [
        ("01", "The Problem", "Irrigation loss, crop stress, soil & climate challenges in Indian & Kashmiri paddy"),
        ("02", "Why Change?", "Evidence for Pipe Distribution Networks and AI/IoT vs conventional methods"),
        ("03", "Our Solution", "Underground PDN + multimodal AI + IoT + human governance"),
        ("04", "How It Works", "Architecture, methodology, sensing tiers, decision loop"),
        ("05", "Impact & Path", "Water efficiency, equity, scalability — and research citations"),
    ]
    for i, (num, title, desc) in enumerate(items):
        y = Inches(1.75) + Inches(i * 0.95)
        add_panel(s, Inches(0.7), y, Inches(12.0), Inches(0.85))
        circ = s.shapes.add_shape(MSO_SHAPE.OVAL, Inches(0.95), y + Inches(0.15), Inches(0.55), Inches(0.55))
        circ.fill.solid()
        circ.fill.fore_color.rgb = TEAL
        circ.line.fill.background()
        ct = circ.text_frame
        ct.paragraphs[0].alignment = PP_ALIGN.CENTER
        r = ct.paragraphs[0].add_run()
        r.text = num
        set_run(r, 14, True, WHITE)
        add_text_box(s, Inches(1.75), y + Inches(0.12), Inches(10.5), Inches(0.35), [title], size=18, color=TEAL_DARK)
        add_text_box(s, Inches(1.75), y + Inches(0.42), Inches(10.5), Inches(0.35), [desc], size=13, color=MUTED)
    add_footer_bar(s, prs, 2, total)

    # ========== 3. PROBLEM — IRRIGATION ==========
    s = prs.slides.add_slide(blank)
    add_bg(s, prs)
    brand_header(s, prs)
    add_title(s, "The Problem: Irrigation Inefficiency in Indian Paddy", top=Inches(0.95))

    stats = [
        ("~45%", "of agricultural water\nlost in conveyance\n(FAO Aquastat / India)"),
        ("30–40%", "overall canal project\nefficiency (CDN upper\nlimit with lining)"),
        ("60–83%", "of applied field water\nlost to deep percolation\nin conventional paddy"),
        ("~40%", "of India's irrigation\nwater used by rice\nalone"),
    ]
    for i, (stat, label) in enumerate(stats):
        x = Inches(0.5) + Inches(i * 3.2)
        add_panel(s, x, Inches(1.85), Inches(3.0), Inches(2.4))
        add_text_box(s, x + Inches(0.1), Inches(2.0), Inches(2.8), Inches(0.7), [stat], size=28, color=TEAL, align=PP_ALIGN.CENTER)
        add_text_box(s, x + Inches(0.15), Inches(2.75), Inches(2.7), Inches(1.3), [label], size=12, color=GRAY, align=PP_ALIGN.CENTER)

    bullet_panel(
        s, Inches(0.5), Inches(4.45), Inches(12.3), Inches(2.35),
        "What this means for farmers",
        [
            "Open canals lose water to seepage, evaporation, siltation and uneven delivery — tail-end farmers often receive least water.",
            "Conventional transplanted paddy needs 3,000–5,000 L water per kg grain; puddling alone can consume 150–250 mm extra water.",
            "Kashmir context: silted / blocked canals, groundwater dependence, and drought years (e.g., low snowfall 2017–18) threaten paddy sowing.",
            "Uneven terrain makes gravity canals harder to manage; inequitable distribution persists across villages.",
        ],
        bullet_size=13,
    )
    add_footer_bar(s, prs, 3, total)

    # ========== 4. PROBLEM — CROP & CLIMATE ==========
    s = prs.slides.add_slide(blank)
    add_bg(s, prs)
    brand_header(s, prs)
    add_title(s, "The Problem: Crop Production & Climate Stress (Kashmir / India)", top=Inches(0.95))

    bullet_panel(
        s, Inches(0.45), Inches(1.8), Inches(6.1), Inches(4.8),
        "Kashmir & J&K realities",
        [
            "Rice is the staple crop and core of food security in Kashmir Valley.",
            "Only ~42% of agricultural land historically irrigated; rest rain-dependent.",
            "Climate projections: rice yields may fall ~6.6%/year by 2040 and ~29% by 2090 (Romshoo & Muslim).",
            "Erratic rainfall, heatwaves, hail, groundwater depletion and rising pest pressure reported in J&K farm studies.",
            "Canal maintenance gaps force farmers onto tube wells — unsustainable under drought.",
            "Temperate Himalayan constraints limit simple transfer of AWD / aerobic rice without precision support.",
        ],
        bullet_size=13,
    )
    bullet_panel(
        s, Inches(6.8), Inches(1.8), Inches(6.1), Inches(4.8),
        "Production bottlenecks",
        [
            "Waterlogging and deficit zones coexist in the same command area.",
            "Stage-specific water needs (tillering, panicle, flowering) are poorly matched by rotational canal schedules.",
            "Labour scarcity and delayed irrigation reduce yield stability.",
            "No real-time link between weather forecasts and field irrigation decisions.",
            "Farmers lack transparent data on how much water their village / field actually received.",
            "Governance gap: departments cannot see field-level demand, losses or inequities in real time.",
        ],
        bullet_size=13,
    )
    add_footer_bar(s, prs, 4, total)

    # ========== 5. PROBLEM — SOIL / CROP HEALTH ==========
    s = prs.slides.add_slide(blank)
    add_bg(s, prs)
    brand_header(s, prs)
    add_title(s, "The Problem: Soil Health, Crop Health, Pests & Waterlogging", top=Inches(0.95))

    cards = [
        ("Soil health", ["Nutrient imbalance (N-P-K)", "Moisture extremes", "EC / salinity risk", "Poor root-zone oxygen under continuous flooding"]),
        ("Crop health", ["Water-stress vs waterlogging confusion", "Chlorophyll / canopy decline", "Stage-mismatched irrigation", "Yield gaps from late stress"]),
        ("Pests & disease", ["Humidity-driven outbreaks", "Climate-linked pest surges", "Late detection → chemical overuse", "No spatial hotspot mapping"]),
        ("Waterlogging", ["Deep percolation losses", "Yield & soil structure damage", "Canal oversupply at head reaches", "Hard to detect early without sensors / imagery"]),
    ]
    for i, (title, bullets) in enumerate(cards):
        x = Inches(0.4) + Inches(i * 3.25)
        bullet_panel(s, x, Inches(1.85), Inches(3.1), Inches(4.7), title, bullets, bullet_size=13)
    add_footer_bar(s, prs, 5, total)

    # ========== 6. WHY PDN ==========
    s = prs.slides.add_slide(blank)
    add_bg(s, prs)
    brand_header(s, prs)
    add_title(s, "Why Underground Pipe Irrigation (PDN) over Canals (CDN)?", top=Inches(0.95))

    # Comparison table-like panels
    add_panel(s, Inches(0.45), Inches(1.8), Inches(6.1), Inches(4.8))
    add_text_box(s, Inches(0.7), Inches(1.95), Inches(5.6), Inches(0.4), ["Canal Distribution Network (CDN)"], size=18, color=RGBColor(0xA0, 0x40, 0x30))
    add_text_box(
        s, Inches(0.7), Inches(2.5), Inches(5.6), Inches(3.8),
        [
            "• Overall efficiency typically 30–40% (design), often 20–35% in practice",
            "• Heavy seepage, evaporation & theft losses",
            "• Large land acquisition / right-of-way",
            "• Poor fit for undulating / hilly terrain",
            "• Siltation & maintenance burden",
            "• Inequitable head vs tail delivery",
            "• Limited volumetric control per village/field",
        ],
        size=14, color=GRAY,
    )

    add_panel(s, Inches(6.8), Inches(1.8), Inches(6.1), Inches(4.8))
    add_text_box(s, Inches(7.05), Inches(1.95), Inches(5.6), Inches(0.4), ["Pipe Distribution Network (PDN)"], size=18, color=GREEN)
    add_text_box(
        s, Inches(7.05), Inches(2.5), Inches(5.6), Inches(3.8),
        [
            "• Overall efficiency ~70–80% (CWC / PDN studies)",
            "• Minimal evaporation; reduced seepage",
            "• Buried pipes → less land take",
            "• Strong advantage on uneven terrain",
            "• Filters + pressure control reduce silt / surge issues",
            "• Volumetric, timed, village-wise & field-wise control",
            "• Higher CAPEX upfront; better lifecycle water & equity returns",
        ],
        size=14, color=GRAY,
    )
    add_footer_bar(s, prs, 6, total)

    # ========== 7. WHY AI IOT ==========
    s = prs.slides.add_slide(blank)
    add_bg(s, prs)
    brand_header(s, prs)
    add_title(s, "Why AI + IoT over Basin Irrigation, Manual AWD & Fixed Schedules?", top=Inches(0.95))

    rows = [
        ("Continuous flooding / basin", "High water use; waterlogging risk; no demand sensing", "Baseline farmer practice"),
        ("Manual AWD", "Saves ~20–50% water vs flooding, but needs constant field checks", "Labour & error limited"),
        ("Sensor / IoT AWD", "+13–20% extra water saving vs manual AWD; energy cost ↓ ~25%", "Evidence from Mekong Delta & Bangladesh trials"),
        ("Automated AWD", "~20% less irrigation water vs conventional; WUE up to ~65 kg/ha/cm", "Peer-reviewed automated systems"),
        ("Our multimodal AI platform", "Fuses sensors + weather + imagery + hydraulics + governance", "Demand-driven, forecast-aware, fail-safe"),
    ]
    add_panel(s, Inches(0.4), Inches(1.75), Inches(12.5), Inches(4.9))
    headers = ["Approach", "What research shows", "Limitation / opportunity"]
    xs = [Inches(0.55), Inches(3.6), Inches(8.4)]
    for h, x in zip(headers, xs):
        add_text_box(s, x, Inches(1.9), Inches(4.0), Inches(0.35), [h], size=13, color=TEAL)
    for i, (a, b, c) in enumerate(rows):
        y = Inches(2.35) + Inches(i * 0.75)
        add_text_box(s, xs[0], y, Inches(2.9), Inches(0.7), [a], size=12, color=TEAL_DARK)
        add_text_box(s, xs[1], y, Inches(4.6), Inches(0.7), [b], size=12, color=GRAY)
        add_text_box(s, xs[2], y, Inches(4.2), Inches(0.7), [c], size=12, color=MUTED)
    add_footer_bar(s, prs, 7, total)

    # ========== 8. SOLUTION ==========
    s = prs.slides.add_slide(blank)
    add_bg(s, prs)
    brand_header(s, prs)
    add_title(s, "Our Solution: Smart Irrigation Control + Governance", top=Inches(0.95))

    points = [
        (False, "Deploy soil-moisture & water-level sensors in representative fields"),
        (False, "Predict stage-specific paddy water requirements using AI models"),
        (False, "IoT monitors field water depth, soil moisture, microclimate & pipe flow"),
        (False, "Cloud AI predicts water demand 3–6 hours ahead"),
        (True, "Automated valve control at source, junctions & lateral outlets"),
        (True, "Reduces water loss, prevents waterlogging, improves yields"),
        (True, "Scalable sensing + human dashboard override as AI fail-safe"),
    ]
    for i, (is_green, t) in enumerate(points):
        y = Inches(1.7) + Inches(i * 0.7)
        shape = add_panel(s, Inches(1.2), y, Inches(10.9), Inches(0.6))
        shape.fill.fore_color.rgb = (
            RGBColor(0xD8, 0xF0, 0xE6) if is_green else RGBColor(0xD6, 0xEE, 0xF2)
        )
        color = GREEN if is_green else TEAL_DARK
        add_text_box(
            s, Inches(1.5), y + Inches(0.12), Inches(10.4), Inches(0.4),
            [f"{i+1}.  {t}"], size=14, color=color,
        )
    add_footer_bar(s, prs, 8, total)

    # ========== 9. ARCHITECTURE PDN ==========
    s = prs.slides.add_slide(blank)
    add_bg(s, prs)
    brand_header(s, prs)
    add_title(s, "Physical Layer: Intelligent Underground PDN", top=Inches(0.95))

    levels = [
        ("Source Station", ["Filters (silt/sediment)", "Flow / pressure / turbidity", "Quality sensors", "Main actuator / gate", "Solar + battery + LoRa"]),
        ("Main → Sub-main", ["Village junction nodes", "Volume & pressure sensors", "Actuators for allocation", "Booster / PRV where needed", "Leakage indicators"]),
        ("Laterals & Outlets", ["Field laterals like drip layout", "Outlet valves + sensors", "Section-wise control", "Ideal for uneven terrain", "Demand-driven release"]),
        ("Reference Fields", ["Dense sensing (moisture, NPK, EC, pH, depth)", "Ground-truth for AI", "Sparse sensing elsewhere", "UAV / satellite fusion", "Closed-loop verify"]),
    ]
    for i, (title, bullets) in enumerate(levels):
        x = Inches(0.35) + Inches(i * 3.25)
        bullet_panel(s, x, Inches(1.8), Inches(3.1), Inches(4.75), title, bullets, bullet_size=12)
    add_footer_bar(s, prs, 9, total)

    # ========== 10. METHODOLOGY 3 LEVELS ==========
    s = prs.slides.add_slide(blank)
    add_bg(s, prs)
    brand_header(s, prs)
    add_title(s, "Methodology: Multi-Level Architecture", top=Inches(0.95))

    tiers = [
        (RGBColor(0x1A, 0x8F, 0x6E), "Level 1 — Field (Farmer + Sensor Node)",
         "IoT: water depth, soil moisture, temp/RH, flow. AI predicts daily/weekly need from phenology, ET₀, Kc, soil & weather. Output: timing, duration, alerts."),
        (RGBColor(0x00, 0x7A, 0x96), "Level 2 — Village / Cluster",
         "Aggregates field data. Predicts 7–14 day village demand. Optimizes distribution among farmers. Flags deficit / excess zones."),
        (RGBColor(0xC4, 0x6B, 0x2E), "Level 3 — Government / Irrigation Dept.",
         "Monitors source discharge, reservoirs, lift systems. AI recommends conveyance, scheduling & diversion. Supports drought, rotational & emergency policy."),
    ]
    for i, (color, title, body) in enumerate(tiers):
        y = Inches(1.8) + Inches(i * 1.55)
        bar = s.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(0.55), y, Inches(12.2), Inches(1.4))
        bar.fill.solid()
        bar.fill.fore_color.rgb = color
        bar.line.fill.background()
        bar.adjustments[0] = 0.08
        add_text_box(s, Inches(0.85), y + Inches(0.18), Inches(11.6), Inches(0.4), [title], size=18, color=WHITE)
        add_text_box(s, Inches(0.85), y + Inches(0.65), Inches(11.6), Inches(0.6), [body], size=13, color=WHITE)
    add_footer_bar(s, prs, 10, total)

    # ========== 11. HYBRID AI ==========
    s = prs.slides.add_slide(blank)
    add_bg(s, prs)
    brand_header(s, prs)
    add_title(s, "Hybrid AI Architecture", top=Inches(0.95))

    ai_blocks = [
        ("Rule-Based Safety Engine", "Hard constraints — block irrigation under excessive depth, sensor faults, low groundwater / source limits."),
        ("Time-Series ML Models", "LSTM/GRU or Random Forest — forecast water need 3–6 hours ahead from continuous sensor streams."),
        ("Waterlogging & Stress Classifier", "Fuses sensor trends with elevation, canal/pipe branch, upstream–downstream flow & imagery indices."),
        ("Source-Level Monitoring", "Accurate volumetric delivery per village — transparency, equity and leakage detection."),
        ("Multimodal Fusion", "Ground sensors + multi/hyperspectral imagery + weather forecasts + hydraulic network state."),
        ("Human-in-the-Loop", "High-confidence acts auto; high-impact / low-confidence needs operator or farmer approval."),
    ]
    for i, (t, b) in enumerate(ai_blocks):
        col = i % 3
        row = i // 3
        x = Inches(0.4) + Inches(col * 4.3)
        y = Inches(1.8) + Inches(row * 2.4)
        bullet_panel(s, x, y, Inches(4.1), Inches(2.2), t, [b], bullet_size=13)
    add_footer_bar(s, prs, 11, total)

    # ========== 12. DATA INPUTS ==========
    s = prs.slides.add_slide(blank)
    add_bg(s, prs)
    brand_header(s, prs)
    add_title(s, "Data Inputs for AI Models", top=Inches(0.95))

    inputs = [
        "Soil moisture, water depth, water table (continuous sensors)",
        "Rainfall & weather (temp, humidity, wind, radiation, ET₀)",
        "Crop stage & phenology (NDVI / hyperspectral from drone–satellite)",
        "Historical irrigation volumes & field metadata",
        "Source supply & pipe/canal flow telemetry",
        "Spatial data: soil type, elevation, pipe proximity",
        "Pest / disease observations & hotspot alerts",
        "Hydraulic state: pressure, valve position, battery / solar",
        "Drought / snowfall–snowmelt indicators where relevant",
    ]
    for i, text in enumerate(inputs):
        col = i % 3
        row = i // 3
        x = Inches(0.4) + Inches(col * 4.3)
        y = Inches(1.85) + Inches(row * 1.55)
        add_panel(s, x, y, Inches(4.1), Inches(1.35))
        add_text_box(s, x + Inches(0.2), y + Inches(0.35), Inches(3.7), Inches(0.8), [text], size=14, color=TEAL_DARK, align=PP_ALIGN.CENTER)
    add_footer_bar(s, prs, 12, total)

    # ========== 13. CLOSED LOOP ==========
    s = prs.slides.add_slide(blank)
    add_bg(s, prs)
    brand_header(s, prs)
    add_title(s, "Closed Loop: Sense → Analyze → Decide → Actuate → Govern", top=Inches(0.95))

    steps = ["Sense", "Analyze", "Decide", "Actuate", "Verify", "Govern"]
    for i, step in enumerate(steps):
        x = Inches(0.55) + Inches(i * 2.15)
        oval = s.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, x, Inches(1.85), Inches(1.95), Inches(0.7))
        oval.fill.solid()
        oval.fill.fore_color.rgb = TEAL if i % 2 == 0 else ACCENT
        oval.line.fill.background()
        oval.adjustments[0] = 0.2
        add_text_box(s, x, Inches(1.98), Inches(1.95), Inches(0.45), [step], size=16, color=WHITE, align=PP_ALIGN.CENTER)
        if i < 5:
            add_text_box(s, x + Inches(1.85), Inches(1.95), Inches(0.35), Inches(0.4), ["→"], size=18, color=TEAL_DARK)

    bullet_panel(
        s, Inches(0.45), Inches(2.85), Inches(6.1), Inches(3.7),
        "Intelligence & connectivity",
        [
            "Sensors/actuators ↔ LoRaWAN ↔ village gateway ↔ cloud",
            "Imagery & weather use broadband / cloud pipelines (not LoRa)",
            "Edge safety rules keep irrigation alive if cloud drops",
            "AI outputs recommendation + confidence score",
            "Automatic / human approval / emergency override paths",
        ],
        bullet_size=13,
    )
    bullet_panel(
        s, Inches(6.8), Inches(2.85), Inches(6.1), Inches(3.7),
        "Governance surfaces",
        [
            "Govt dashboard: allocation vs use, leaks, equity, drought",
            "Farmer app: moisture, schedule, health alerts, override request",
            "Operator console: valves, pressure, faults, battery/solar",
            "Audit trail of AI decisions and human interventions",
            "Village → town → district aggregation for policy",
        ],
        bullet_size=13,
    )
    add_footer_bar(s, prs, 13, total)

    # ========== 14. IMPLEMENTATION ==========
    s = prs.slides.add_slide(blank)
    add_bg(s, prs)
    brand_header(s, prs)
    add_title(s, "Implementation Strategy (Phased Pilot → Scale)", top=Inches(0.95))

    steps = [
        ("1. Selection", "Choose critical fields by soil, elevation, pipe branch & farmer readiness"),
        ("2. Deployment", "Dense sensors on reference plots; cluster remaining fields for prediction"),
        ("3. Connectivity", "LoRa/LoRaWAN gateways; solar + battery at every intelligent node"),
        ("4. Hydraulics", "Model pressure/flow; add boosters/PRVs where gravity is insufficient"),
        ("5. Cloud ML", "TensorFlow / PyTorch / Scikit-learn on continuous streams"),
        ("6. Remote sensing", "Drone/satellite NDVI & water indices for early stress / waterlogging"),
        ("7. Governance UX", "Department dashboard + farmer app + fail-safe override"),
        ("8. Scale-up", "Prove water & yield gains in pilot cluster; expand village → town"),
    ]
    for i, (t, b) in enumerate(steps):
        col = i % 4
        row = i // 4
        x = Inches(0.35) + Inches(col * 3.25)
        y = Inches(1.85) + Inches(row * 2.4)
        bullet_panel(s, x, y, Inches(3.1), Inches(2.2), t, [b], bullet_size=13)
    add_footer_bar(s, prs, 14, total)

    # ========== 15. IMPACT ==========
    s = prs.slides.add_slide(blank)
    add_bg(s, prs)
    brand_header(s, prs)
    add_title(s, "How This Resolves the Problems We Faced", top=Inches(0.95))

    impact = [
        ("Conveyance loss", "PDN lifts system efficiency from ~30–40% toward ~70–80%"),
        ("Inequity", "Volumetric metering at village & outlet level — transparent allocation"),
        ("Waterlogging / drought", "Forecast-aware, field-specific open/close — not blanket canal turns"),
        ("Crop & soil health", "Multimodal early stress, nutrient & pest signals before yield loss"),
        ("Climate risk (Kashmir)", "Uses weather + snowmelt/drought indicators in decisions"),
        ("Governance gap", "Government digital twin of water use with human override fail-safe"),
        ("Uneven terrain", "Pressurized / booster-assisted PDN outperforms open canals"),
        ("CAPEX concern", "Phased pilot proves savings; compare CAPEX+OPEX vs water & yield gains"),
    ]
    for i, (t, b) in enumerate(impact):
        col = i % 4
        row = i // 4
        x = Inches(0.35) + Inches(col * 3.25)
        y = Inches(1.8) + Inches(row * 2.4)
        bullet_panel(s, x, y, Inches(3.1), Inches(2.2), t, [b], bullet_size=13)
    add_footer_bar(s, prs, 15, total)

    # ========== 16. REFERENCES ==========
    s = prs.slides.add_slide(blank)
    add_bg(s, prs)
    brand_header(s, prs)
    add_title(s, "Key References & Citations", top=Inches(0.95), size=28)

    refs = [
        "1. FAO Aquastat / hydrospatial studies — ~45% agricultural water lost in Indian canal conveyance; Dudhganga case ~40% loss (Hindawi, 2019).",
        "2. CWC / PDN literature — CDN overall efficiency ~30–40% (often 20–35% actual); PDN ~70–80% (Kolhe 2012; CWC piped irrigation guidelines).",
        "3. ICRISAT / Agricultural Research (2025) — Rice uses ~40% of India’s irrigation water; 60–83% applied water lost to deep percolation.",
        "4. Sandesh & Valunjkar (IJRASET, 2017) — Feasibility of PDN over CDN; higher application efficiency; better for undulating terrain.",
        "5. Paik et al. (Agronomy for Sustainable Development, 2022) — IoT AWD in Mekong Delta: +13–20% water savings vs manual AWD; ~25% energy cost cut.",
        "6. Automated AWD studies (Bangladesh / AgriEngineering) — ~20–36% irrigation water reduction vs continuous flooding; higher WUE.",
        "7. Bhat et al. / Kashmir rice reviews — drought & low snowfall threaten valley paddy; need efficient water use under Indus constraints.",
        "8. Climate–agriculture studies (J&K) — silted canals, groundwater depletion, pest/climate stress; irrigation coverage historically ~42%.",
        "9. Scientific Reports (2025, Kashmir) — temperate limits of AWD/aerobic rice; need for sensor-based precision irrigation & N management.",
        "10. Bouman & Tuong (Agric. Water Manage., 2001); Lampayan et al. (Field Crops Res., 2015) — foundations of AWD water savings in rice.",
    ]
    box = s.shapes.add_textbox(Inches(0.45), Inches(1.7), Inches(12.4), Inches(5.1))
    tf = box.text_frame
    tf.word_wrap = True
    for i, r in enumerate(refs):
        p = tf.paragraphs[0] if i == 0 else tf.add_paragraph()
        p.space_after = Pt(4)
        run = p.add_run()
        run.text = r
        set_run(run, 11, False, GRAY)
    add_footer_bar(s, prs, 16, total)

    prs.save(OUT)
    print("Saved:", OUT)
    return OUT


if __name__ == "__main__":
    make_prs()
