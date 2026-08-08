from __future__ import annotations

import textwrap
from pathlib import Path

import fitz
from PIL import Image, ImageDraw


ROOT = Path(__file__).resolve().parents[1]
OUT_DIR = ROOT / "output" / "pdf"
TMP_DIR = ROOT / "tmp" / "pdfs"
PDF_PATH = OUT_DIR / "Alexandra-Domain-Transfer-Strategic-Timeline-Report.pdf"
WARNINGS: list[str] = []


PAGE_W, PAGE_H = fitz.paper_size("a4")
MARGIN = 42
BOTTOM = PAGE_H - 48
BLUE = (0.08, 0.23, 0.38)
TEAL = (0.0, 0.45, 0.42)
GREEN = (0.11, 0.47, 0.25)
AMBER = (0.72, 0.40, 0.03)
RED = (0.72, 0.12, 0.12)
DARK = (0.12, 0.16, 0.19)
MID = (0.33, 0.38, 0.42)
LIGHT = (0.94, 0.97, 0.98)
PANEL = (0.985, 0.988, 0.99)
LINE = (0.78, 0.84, 0.88)


def clean(s: str) -> str:
    replacements = {
        "\u2013": "-",
        "\u2014": "-",
        "\u2018": "'",
        "\u2019": "'",
        "\u201c": '"',
        "\u201d": '"',
        "\u00a0": " ",
    }
    for old, new in replacements.items():
        s = s.replace(old, new)
    return s


def draw_text(
    page: fitz.Page,
    text: str,
    rect: fitz.Rect,
    size: float = 10,
    color=DARK,
    font: str = "helv",
    align: int = fitz.TEXT_ALIGN_LEFT,
    lineheight: float | None = None,
) -> None:
    text = clean(text)
    if not text:
        return
    leading = size * (lineheight if lineheight else 1.2)
    y = rect.y0 + size
    max_w = max(12, rect.width)

    def split_long_word(word: str) -> list[str]:
        chunks: list[str] = []
        current = ""
        for ch in word:
            test = current + ch
            if current and fitz.get_text_length(test, fontname=font, fontsize=size) > max_w:
                chunks.append(current)
                current = ch
            else:
                current = test
        if current:
            chunks.append(current)
        return chunks

    def wrap_line(raw: str) -> list[str]:
        words: list[str] = []
        for word in raw.split():
            if fitz.get_text_length(word, fontname=font, fontsize=size) > max_w:
                words.extend(split_long_word(word))
            else:
                words.append(word)
        if not words:
            return [""]
        lines: list[str] = []
        current = words[0]
        for word in words[1:]:
            test = current + " " + word
            if fitz.get_text_length(test, fontname=font, fontsize=size) <= max_w:
                current = test
            else:
                lines.append(current)
                current = word
        lines.append(current)
        return lines

    lines: list[str] = []
    for raw_line in text.splitlines():
        lines.extend(wrap_line(raw_line))

    for line in lines:
        if y > rect.y1:
            WARNINGS.append(f"Text overflow on page {page.number + 1}: {text[:80]}")
            return
        text_w = fitz.get_text_length(line, fontname=font, fontsize=size)
        if align == fitz.TEXT_ALIGN_RIGHT:
            x = rect.x1 - text_w
        elif align == fitz.TEXT_ALIGN_CENTER:
            x = rect.x0 + (rect.width - text_w) / 2
        else:
            x = rect.x0
        page.insert_text((x, y), line, fontsize=size, fontname=font, color=color)
        y += leading


def add_footer(page: fitz.Page, num: int) -> None:
    page.draw_line((MARGIN, PAGE_H - 36), (PAGE_W - MARGIN, PAGE_H - 36), color=LINE, width=0.6)
    draw_text(
        page,
        "Alexandra Montessori - Domain Transfer Strategic Timeline",
        fitz.Rect(MARGIN, PAGE_H - 31, PAGE_W - 130, PAGE_H - 15),
        size=7.5,
        color=MID,
    )
    draw_text(
        page,
        f"Page {num}",
        fitz.Rect(PAGE_W - 105, PAGE_H - 31, PAGE_W - MARGIN, PAGE_H - 15),
        size=7.5,
        color=MID,
        align=fitz.TEXT_ALIGN_RIGHT,
    )


def add_header(page: fitz.Page, title: str) -> None:
    draw_text(page, title, fitz.Rect(MARGIN, 27, PAGE_W - MARGIN, 48), size=11.5, color=BLUE, font="hebo")
    page.draw_line((MARGIN, 53), (PAGE_W - MARGIN, 53), color=LINE, width=0.8)


def panel(page: fitz.Page, x: float, y: float, w: float, h: float, fill=PANEL, stroke=LINE, radius=8) -> fitz.Rect:
    r = fitz.Rect(x, y, x + w, y + h)
    page.draw_rect(r, color=stroke, fill=fill, width=0.7)
    return r


def section_title(page: fitz.Page, title: str, x: float, y: float, color=BLUE) -> float:
    draw_text(page, title, fitz.Rect(x, y, PAGE_W - MARGIN, y + 24), size=15, color=color, font="hebo")
    return y + 28


def bullet_list(page: fitz.Page, items: list[str], x: float, y: float, w: float, size=9.3, gap=6, color=DARK) -> float:
    for item in items:
        item = clean(item)
        wrapped = textwrap.wrap(item, width=max(36, int(w / (size * 0.48))))
        h = max(16, len(wrapped) * (size + 2))
        draw_text(page, "-", fitz.Rect(x, y, x + 10, y + 13), size=size, color=color)
        draw_text(page, "\n".join(wrapped), fitz.Rect(x + 13, y, x + w, y + h + 4), size=size, color=color)
        y += h + gap
    return y


def numbered_list(page: fitz.Page, items: list[str], x: float, y: float, w: float, size=9.3, gap=7) -> float:
    for idx, item in enumerate(items, 1):
        wrapped = textwrap.wrap(clean(item), width=max(34, int(w / (size * 0.49))))
        h = max(17, len(wrapped) * (size + 2))
        draw_text(page, f"{idx}.", fitz.Rect(x, y, x + 18, y + 14), size=size, color=BLUE, font="hebo")
        draw_text(page, "\n".join(wrapped), fitz.Rect(x + 22, y, x + w, y + h + 4), size=size, color=DARK)
        y += h + gap
    return y


def callout(page: fitz.Page, title: str, body: str, x: float, y: float, w: float, h: float, color=TEAL) -> None:
    r = panel(page, x, y, w, h, fill=(0.97, 0.99, 0.985), stroke=color, radius=7)
    draw_text(page, title, fitz.Rect(r.x0 + 13, r.y0 + 11, r.x1 - 13, r.y0 + 31), size=10.5, color=color, font="hebo")
    draw_text(page, body, fitz.Rect(r.x0 + 13, r.y0 + 34, r.x1 - 13, r.y1 - 10), size=8.8, color=DARK, lineheight=1.15)


def make_doc() -> fitz.Document:
    doc = fitz.open()

    # Page 1
    page = doc.new_page(width=PAGE_W, height=PAGE_H)
    page.draw_rect(fitz.Rect(0, 0, PAGE_W, 132), fill=(0.94, 0.98, 0.98), color=None)
    draw_text(page, "Alexandra Montessori", fitz.Rect(MARGIN, 56, PAGE_W - MARGIN, 82), size=22, color=BLUE, font="hebo")
    draw_text(page, "Domain Transfer and Website Launch", fitz.Rect(MARGIN, 88, PAGE_W - MARGIN, 112), size=17, color=TEAL, font="hebo")
    draw_text(page, "Strategic Timeline Report", fitz.Rect(MARGIN, 116, PAGE_W - MARGIN, 142), size=13.5, color=DARK, font="hebo")
    draw_text(page, "Prepared: 19 July 2026   |   Domain: alexandramontessori.co.uk", fitz.Rect(MARGIN, 154, PAGE_W - MARGIN, 175), size=9.5, color=MID)

    draw_text(
        page,
        "Purpose",
        fitz.Rect(MARGIN, 204, PAGE_W - MARGIN, 226),
        size=15,
        color=BLUE,
        font="hebo",
    )
    draw_text(
        page,
        clean(
            "This report converts the technical DNS handover into a practical timeline: what to do first, what must wait, what each party is responsible for, and how to avoid breaking the website or email while moving control to Hostinger."
        ),
        fitz.Rect(MARGIN, 232, PAGE_W - MARGIN, 287),
        size=10.6,
        color=DARK,
        lineheight=1.18,
    )

    y = 318
    callout(
        page,
        "Main Decision",
        "Move the domain registration to Hostinger first, but keep the existing nameservers active until the new DNS zone is copied and checked.",
        MARGIN,
        y,
        PAGE_W - (MARGIN * 2),
        76,
        color=TEAL,
    )
    y += 100
    callout(
        page,
        "Critical Safety Rule",
        "Do not change nameservers and do not edit email records during the transfer. Registration transfer and DNS cutover are separate stages.",
        MARGIN,
        y,
        PAGE_W - (MARGIN * 2),
        76,
        color=AMBER,
    )
    y += 108
    draw_text(page, "What success looks like", fitz.Rect(MARGIN, y, PAGE_W - MARGIN, y + 22), size=14, color=BLUE, font="hebo")
    y += 28
    y = bullet_list(
        page,
        [
            "The client controls the domain in a Hostinger account with billing, recovery and two-factor authentication secured.",
            "The new WordPress website opens on alexandramontessori.co.uk and www.alexandramontessori.co.uk.",
            "Existing email keeps working while the website moves.",
            "There is a rollback path for the first week after launch.",
        ],
        MARGIN,
        y,
        PAGE_W - (MARGIN * 2),
        size=10,
    )
    add_footer(page, 1)

    # Page 2
    page = doc.new_page(width=PAGE_W, height=PAGE_H)
    add_header(page, "Timeline Overview")
    y = 75
    timeline = [
        ("0", "Today", "Pause the tag change until Hostinger transfer order is ready. Ask Nigel for the full DNS zone export.", "Prevents the domain being sent before Hostinger can receive it."),
        ("1", "Transfer Order", "Start incoming .co.uk transfer in Hostinger. Choose to keep current nameservers.", "Registration moves without changing live DNS."),
        ("2", "Registrar Release", "When Hostinger is ready, Nigel changes the IPS tag to AXIDOMAINS.", "Hostinger can attach the domain to your account."),
        ("3", "DNS Preparation", "Copy all existing DNS records into Hostinger, preserving email records exactly.", "Avoids email loss when nameservers later move."),
        ("4", "Website Launch", "Point web records to the Hostinger website, test SSL, forms, pages and WordPress admin.", "Website goes live under the real domain."),
        ("5", "Stabilise", "Monitor website and email for 7 days before cancelling old DNS or support.", "Keeps rollback available while issues are most likely."),
        ("6", "Operate", "Run backups, updates, content changes, form checks and renewal reviews.", "Turns launch into a maintainable system."),
    ]
    line_x = MARGIN + 22
    page.draw_line((line_x, y + 8), (line_x, 690), color=LINE, width=2)
    for step, label, action, safety in timeline:
        page.draw_circle((line_x, y + 10), 11, color=TEAL, fill=(1, 1, 1), width=1.5)
        draw_text(page, step, fitz.Rect(line_x - 5, y + 3, line_x + 6, y + 18), size=8.5, color=TEAL, font="hebo", align=fitz.TEXT_ALIGN_CENTER)
        draw_text(page, label, fitz.Rect(MARGIN + 52, y, MARGIN + 150, y + 18), size=11.5, color=BLUE, font="hebo")
        draw_text(page, action, fitz.Rect(MARGIN + 152, y - 1, PAGE_W - MARGIN, y + 30), size=9.2, color=DARK, lineheight=1.08)
        draw_text(page, "Safety: " + safety, fitz.Rect(MARGIN + 152, y + 33, PAGE_W - MARGIN, y + 61), size=8.4, color=MID, lineheight=1.08)
        y += 88
    callout(
        page,
        "Do not combine stages",
        "Most problems happen when registration transfer, nameserver change, website pointing and email cleanup are done at the same time. Keep them separate.",
        MARGIN,
        706,
        PAGE_W - 2 * MARGIN,
        65,
        color=RED,
    )
    add_footer(page, 2)

    # Page 3
    page = doc.new_page(width=PAGE_W, height=PAGE_H)
    add_header(page, "Stage 0 - Immediate Control Actions")
    y = section_title(page, "Send this now", MARGIN, 75, color=BLUE)
    callout(
        page,
        "Message to Nigel",
        "Please hold the IPS-tag change until we confirm that the incoming transfer order has been initiated and confirmed in Hostinger. Please also provide a complete export of the existing DNS zone and keep the current DNS/nameservers active during the transfer and for seven days after the agreed DNS cutover.",
        MARGIN,
        y,
        PAGE_W - 2 * MARGIN,
        104,
        color=TEAL,
    )
    y += 132
    draw_text(page, "Ask Nigel for", fitz.Rect(MARGIN, y, PAGE_W - MARGIN, y + 20), size=13, color=BLUE, font="hebo")
    y += 27
    y = bullet_list(
        page,
        [
            "Full DNS zone export: A, AAAA, CNAME, MX, TXT, SPF, DKIM, DMARC, CAA, SRV and subdomains.",
            "TTL values and MX priorities.",
            "Confirmation that current DNS will remain active during transfer and for 7 days after launch.",
            "Confirmation that no website files, old platform transfer or email migration is required from them.",
            "Confirmation of their charge before any paid work is authorised.",
        ],
        MARGIN,
        y,
        PAGE_W - 2 * MARGIN,
    )
    y += 18
    draw_text(page, "Your safe position", fitz.Rect(MARGIN, y, PAGE_W - MARGIN, y + 20), size=13, color=BLUE, font="hebo")
    y += 27
    y = bullet_list(
        page,
        [
            "You are only requesting domain and DNS handover help, not buying their old website platform.",
            "You do not authorise paid technical work until they give a cost and exact scope.",
            "You do not let any provider delete old DNS until the new site and email are confirmed working.",
        ],
        MARGIN,
        y,
        PAGE_W - 2 * MARGIN,
    )
    add_footer(page, 3)

    # Page 4
    page = doc.new_page(width=PAGE_W, height=PAGE_H)
    add_header(page, "Stage 1 - Hostinger Transfer")
    y = section_title(page, "What you do in Hostinger", MARGIN, 75, color=BLUE)
    y = numbered_list(
        page,
        [
            "Log in to the Hostinger account that should own the domain long term.",
            "Go to Domains, then Transfer domain or Transfers.",
            "Enter alexandramontessori.co.uk and start the incoming .co.uk transfer.",
            "Complete payment or order setup if Hostinger requests it.",
            "When asked about nameservers, choose Keep current nameservers.",
            "Confirm the Hostinger transfer email if one arrives.",
            "Only after Hostinger shows the transfer is ready, tell Nigel to change the IPS tag to AXIDOMAINS.",
        ],
        MARGIN,
        y,
        PAGE_W - 2 * MARGIN,
        size=9.6,
    )
    y += 20
    callout(
        page,
        "Do not click yet",
        "Do not change nameservers, DNS records, WordPress domain settings or email settings during this stage. This stage is only about registrar ownership.",
        MARGIN,
        y,
        PAGE_W - 2 * MARGIN,
        78,
        color=AMBER,
    )
    y += 103
    draw_text(page, "After the transfer completes", fitz.Rect(MARGIN, y, PAGE_W - MARGIN, y + 21), size=13, color=BLUE, font="hebo")
    y += 28
    y = bullet_list(
        page,
        [
            "Check Hostinger shows the domain as active in Domain Portfolio.",
            "Confirm owner/contact details, payment method, renewal date and two-factor authentication.",
            "Confirm the nameservers are still the old sustainable-hosting.co.uk nameservers until DNS preparation is complete.",
        ],
        MARGIN,
        y,
        PAGE_W - 2 * MARGIN,
    )
    add_footer(page, 4)

    # Page 5
    page = doc.new_page(width=PAGE_W, height=PAGE_H)
    add_header(page, "Stage 2 - DNS Preparation")
    y = section_title(page, "Prepare before any nameserver change", MARGIN, 75, color=BLUE)
    draw_text(
        page,
        "DNS is where the website and email are directed. This is the stage where mistakes can break email, so treat it as a copy-and-check exercise first.",
        fitz.Rect(MARGIN, y, PAGE_W - MARGIN, y + 44),
        size=10,
        color=DARK,
        lineheight=1.15,
    )
    y += 62
    col_w = (PAGE_W - 2 * MARGIN - 16) / 2
    panel(page, MARGIN, y, col_w, 205, fill=(0.98, 0.995, 0.99), stroke=TEAL)
    draw_text(page, "Copy exactly", fitz.Rect(MARGIN + 14, y + 13, MARGIN + col_w - 14, y + 35), size=12, color=TEAL, font="hebo")
    bullet_list(
        page,
        [
            "MX email records",
            "SPF, DKIM and DMARC TXT records",
            "Google verification records",
            "Any subdomain records",
            "TTL and priority values",
        ],
        MARGIN + 14,
        y + 44,
        col_w - 28,
        size=9.1,
    )
    panel(page, MARGIN + col_w + 16, y, col_w, 205, fill=(0.995, 0.985, 0.965), stroke=AMBER)
    draw_text(page, "Change only when ready", fitz.Rect(MARGIN + col_w + 30, y + 13, PAGE_W - MARGIN - 14, y + 35), size=12, color=AMBER, font="hebo")
    bullet_list(
        page,
        [
            "Website A record for @",
            "Website record for www",
            "Nameservers, only after records are copied",
            "No email cleanup during launch",
            "No IPv6 record unless Hostinger gives one",
        ],
        MARGIN + col_w + 30,
        y + 44,
        col_w - 28,
        size=9.1,
    )
    y += 236
    callout(
        page,
        "Current verified DNS position",
        "Registrar: Namesco. Current nameservers: ns1/ns2/ns3/ns4.sustainable-hosting.co.uk. New website is currently working on Hostinger staging at alexandra.krildigital.com. Public domain still points at the old service.",
        MARGIN,
        y,
        PAGE_W - 2 * MARGIN,
        88,
        color=BLUE,
    )
    y += 115
    draw_text(page, "Gate before moving nameservers", fitz.Rect(MARGIN, y, PAGE_W - MARGIN, y + 21), size=13, color=BLUE, font="hebo")
    y += 28
    y = bullet_list(
        page,
        [
            "DNS export received or all records captured by screenshots.",
            "Hostinger DNS zone prepared and reviewed.",
            "Business email tester available for send/receive tests.",
            "Website backup and staging rollback are ready.",
        ],
        MARGIN,
        y,
        PAGE_W - 2 * MARGIN,
    )
    add_footer(page, 5)

    # Page 6
    page = doc.new_page(width=PAGE_W, height=PAGE_H)
    add_header(page, "Stage 3 - Website Launch Day")
    y = section_title(page, "Launch sequence", MARGIN, 75, color=BLUE)
    y = numbered_list(
        page,
        [
            "Take a fresh Hostinger backup and WordPress backup.",
            "Confirm the website works on the staging URL, including forms and admin login.",
            "Confirm the final domain is added to the correct Hostinger hosting plan.",
            "Update website records in the prepared DNS zone to the Hostinger target shown in hPanel.",
            "Switch nameservers only after the prepared DNS zone is complete.",
            "Wait for SSL to issue, then test https://alexandramontessori.co.uk and https://www.alexandramontessori.co.uk.",
            "Test contact form, availability form, career form, email send/receive, sitemap, robots.txt and WordPress admin.",
        ],
        MARGIN,
        y,
        PAGE_W - 2 * MARGIN,
        size=9.5,
    )
    y += 16
    col_w = (PAGE_W - 2 * MARGIN - 16) / 2
    callout(
        page,
        "Expected behaviour",
        "Some users may see the old site and some may see the new site while DNS propagates. That can be normal for up to 24 hours.",
        MARGIN,
        y,
        col_w,
        92,
        color=TEAL,
    )
    callout(
        page,
        "Stop condition",
        "If email stops receiving, stop website changes and restore the previous email DNS records first. Email has priority.",
        MARGIN + col_w + 16,
        y,
        col_w,
        92,
        color=RED,
    )
    y += 122
    draw_text(page, "What not to do on launch day", fitz.Rect(MARGIN, y, PAGE_W - MARGIN, y + 21), size=13, color=BLUE, font="hebo")
    y += 28
    y = bullet_list(
        page,
        [
            "Do not remove Google or existing mail records.",
            "Do not cancel old DNS hosting.",
            "Do not do SPF cleanup or email restructuring.",
            "Do not make major website design/content changes while DNS is moving.",
        ],
        MARGIN,
        y,
        PAGE_W - 2 * MARGIN,
    )
    add_footer(page, 6)

    # Page 7
    page = doc.new_page(width=PAGE_W, height=PAGE_H)
    add_header(page, "Rollback and Safety Controls")
    y = section_title(page, "How you stay safe", MARGIN, 75, color=BLUE)
    scenarios = [
        ("Website fails but email works", "Point web records back to the previous website IP or keep staging available while fixing Hostinger."),
        ("Email fails", "Restore the previous MX/TXT records immediately. Do not continue DNS cleanup until mail passes tests."),
        ("Transfer stalls", "Keep current nameservers active. Open Hostinger support and ask them to attach the .co.uk domain after AXIDOMAINS tag change."),
        ("SSL warning appears", "Wait for Hostinger SSL issue/reissue, then force HTTPS only after certificate is valid."),
        ("Wrong account used", "Stop, document ownership, and transfer to the client-controlled account before relying on it long term."),
    ]
    for title, action in scenarios:
        r = panel(page, MARGIN, y, PAGE_W - 2 * MARGIN, 72, fill=PANEL, stroke=LINE, radius=7)
        draw_text(page, title, fitz.Rect(r.x0 + 13, r.y0 + 10, r.x1 - 13, r.y0 + 29), size=10.7, color=BLUE, font="hebo")
        draw_text(page, action, fitz.Rect(r.x0 + 13, r.y0 + 33, r.x1 - 13, r.y1 - 9), size=8.9, color=DARK, lineheight=1.12)
        y += 84
    y += 8
    callout(
        page,
        "Seven-day rule",
        "Keep old DNS support available for at least seven days after launch. That gives you a fast recovery path while propagation, SSL, forms and email are being proven.",
        MARGIN,
        y,
        PAGE_W - 2 * MARGIN,
        78,
        color=GREEN,
    )
    add_footer(page, 7)

    # Page 8
    page = doc.new_page(width=PAGE_W, height=PAGE_H)
    add_header(page, "After Launch Operations")
    y = section_title(page, "First week", MARGIN, 75, color=BLUE)
    y = bullet_list(
        page,
        [
            "Day 0: test website, forms, admin login, SSL, sitemap and email.",
            "Day 1: repeat checks from another network and phone browser.",
            "Day 3: review form submissions and any client feedback.",
            "Day 7: if stable, close old DNS dependency and archive launch evidence.",
        ],
        MARGIN,
        y,
        PAGE_W - 2 * MARGIN,
    )
    y += 12
    draw_text(page, "Ongoing responsibility", fitz.Rect(MARGIN, y, PAGE_W - MARGIN, y + 21), size=13, color=BLUE, font="hebo")
    y += 31
    table_x = MARGIN
    table_w = PAGE_W - 2 * MARGIN
    row_h = 47
    rows = [
        ("Weekly", "Check forms, submissions, backups and visible website pages."),
        ("Monthly", "Update WordPress core, plugins and theme after taking a backup."),
        ("Quarterly", "Review users, passwords, SEO basics and uptime history."),
        ("Annually", "Confirm domain renewal, hosting renewal, payment method and account recovery."),
    ]
    page.draw_rect(fitz.Rect(table_x, y, table_x + table_w, y + 28), color=BLUE, fill=BLUE)
    draw_text(page, "Cycle", fitz.Rect(table_x + 12, y + 8, table_x + 100, y + 23), size=8.8, color=(1, 1, 1), font="hebo")
    draw_text(page, "Action", fitz.Rect(table_x + 112, y + 8, table_x + table_w - 12, y + 23), size=8.8, color=(1, 1, 1), font="hebo")
    y += 28
    for cycle, action in rows:
        page.draw_rect(fitz.Rect(table_x, y, table_x + table_w, y + row_h), color=LINE, fill=(1, 1, 1), width=0.6)
        draw_text(page, cycle, fitz.Rect(table_x + 12, y + 15, table_x + 100, y + 34), size=9.2, color=TEAL, font="hebo")
        draw_text(page, action, fitz.Rect(table_x + 112, y + 10, table_x + table_w - 12, y + row_h - 8), size=9.1, color=DARK, lineheight=1.12)
        y += row_h
    y += 28
    callout(
        page,
        "Final principle",
        "Domain ownership, DNS, website hosting and email are separate systems. Move one system at a time, test it, then continue.",
        MARGIN,
        y,
        PAGE_W - 2 * MARGIN,
        76,
        color=TEAL,
    )
    add_footer(page, 8)

    # Page 9
    page = doc.new_page(width=PAGE_W, height=PAGE_H)
    add_header(page, "Decision Checklist")
    y = section_title(page, "Use this before each action", MARGIN, 75, color=BLUE)
    checklist = [
        ("Before IPS tag change", "Hostinger transfer order is started and confirmed. Nigel has been told the exact tag: AXIDOMAINS."),
        ("Before DNS move", "Full DNS zone copied into Hostinger, especially email records."),
        ("Before website launch", "Hostinger backup complete. Staging site tested. Final domain added to the right hosting plan."),
        ("Before old service cancellation", "New website and email have worked for at least seven days."),
        ("Before email cleanup", "Client confirms actual email provider and all live mailboxes. Schedule this separately after launch."),
    ]
    for gate, proof in checklist:
        r = panel(page, MARGIN, y, PAGE_W - 2 * MARGIN, 70, fill=(1, 1, 1), stroke=LINE, radius=7)
        page.draw_rect(fitz.Rect(r.x0 + 13, r.y0 + 18, r.x0 + 29, r.y0 + 34), color=TEAL, width=1)
        draw_text(page, gate, fitz.Rect(r.x0 + 42, r.y0 + 11, r.x1 - 13, r.y0 + 30), size=10.5, color=BLUE, font="hebo")
        draw_text(page, proof, fitz.Rect(r.x0 + 42, r.y0 + 33, r.x1 - 13, r.y1 - 8), size=8.7, color=DARK, lineheight=1.1)
        y += 82
    y += 8
    callout(
        page,
        "When to ask for help",
        "Ask before clicking if Hostinger shows Change nameservers, Change domain, Replace website, Delete DNS zone, Reset DNS, Remove MX, or Remove TXT. Those screens can affect live website or email.",
        MARGIN,
        y,
        PAGE_W - 2 * MARGIN,
        82,
        color=RED,
    )
    add_footer(page, 9)

    # Page 10
    page = doc.new_page(width=PAGE_W, height=PAGE_H)
    add_header(page, "Reference Snapshot")
    y = section_title(page, "Verified current position", MARGIN, 75, color=BLUE)
    y = bullet_list(
        page,
        [
            "Domain: alexandramontessori.co.uk.",
            "Current registrar: Namesco / Team Blue Internet Services UK Limited.",
            "Registry expiry: 15 May 2027.",
            "Current nameservers: ns1, ns2, ns3 and ns4.sustainable-hosting.co.uk.",
            "Destination IPS tag for Hostinger: AXIDOMAINS.",
            "New website currently running on Hostinger staging: alexandra.krildigital.com.",
        ],
        MARGIN,
        y,
        PAGE_W - 2 * MARGIN,
    )
    y += 22
    draw_text(page, "Useful official links", fitz.Rect(MARGIN, y, PAGE_W - MARGIN, y + 21), size=13, color=BLUE, font="hebo")
    y += 30
    links = [
        "Nominet RDAP: https://rdap.nominet.uk/uk/domain/alexandramontessori.co.uk",
        "Hostinger .uk transfer: https://www.hostinger.com/support/1771588-how-to-transfer-a-uk-domain-to-hostinger/",
        "Hostinger point domain: https://www.hostinger.com/support/1863967-how-to-point-a-domain-to-hostinger/",
        "Hostinger DNS records: https://www.hostinger.com/support/1583249-how-to-manage-dns-records-at-hostinger/",
        "Namesco change Nominet tag: https://www.names.co.uk/support/articles/how-to-change-a-nominet-tag/",
    ]
    y = bullet_list(page, links, MARGIN, y, PAGE_W - 2 * MARGIN, size=8.7, gap=8)
    y += 26
    callout(
        page,
        "Separate technical appendix",
        "The earlier Markdown guide remains available for detailed DNS records and step-by-step operating notes. This PDF is the strategic working timeline.",
        MARGIN,
        y,
        PAGE_W - 2 * MARGIN,
        80,
        color=TEAL,
    )
    add_footer(page, 10)

    return doc


def render_pngs(pdf_path: Path) -> list[Path]:
    TMP_DIR.mkdir(parents=True, exist_ok=True)
    doc = fitz.open(pdf_path)
    paths = []
    for i, page in enumerate(doc, start=1):
        pix = page.get_pixmap(matrix=fitz.Matrix(1.35, 1.35), alpha=False)
        out = TMP_DIR / f"alexandra-domain-strategy-page-{i:02d}.png"
        pix.save(out)
        paths.append(out)
    return paths


def make_contact_sheet(paths: list[Path]) -> Path:
    thumbs = []
    for path in paths:
        img = Image.open(path).convert("RGB")
        img.thumbnail((260, 370))
        thumbs.append((path, img.copy()))
    cols = 2
    pad = 18
    label_h = 22
    cell_w = 260
    cell_h = 370 + label_h
    rows = (len(thumbs) + cols - 1) // cols
    sheet = Image.new("RGB", (cols * cell_w + (cols + 1) * pad, rows * cell_h + (rows + 1) * pad), "white")
    draw = ImageDraw.Draw(sheet)
    for idx, (path, img) in enumerate(thumbs):
        col = idx % cols
        row = idx // cols
        x = pad + col * (cell_w + pad)
        y = pad + row * (cell_h + pad)
        draw.rectangle([x, y, x + cell_w, y + cell_h - 1], outline=(206, 214, 220), width=1)
        draw.text((x + 8, y + 6), f"Page {idx + 1}", fill=(32, 43, 52))
        sheet.paste(img, (x + (cell_w - img.width) // 2, y + label_h))
    out = TMP_DIR / "alexandra-domain-strategy-contact-sheet.png"
    sheet.save(out)
    return out


def main() -> None:
    OUT_DIR.mkdir(parents=True, exist_ok=True)
    TMP_DIR.mkdir(parents=True, exist_ok=True)
    doc = make_doc()
    doc.save(PDF_PATH, garbage=4, deflate=True)
    doc.close()
    pages = render_pngs(PDF_PATH)
    sheet = make_contact_sheet(pages)
    print(PDF_PATH)
    print(sheet)
    print(len(pages))
    if WARNINGS:
        print("WARNINGS")
        for warning in WARNINGS:
            print(warning)


if __name__ == "__main__":
    main()
