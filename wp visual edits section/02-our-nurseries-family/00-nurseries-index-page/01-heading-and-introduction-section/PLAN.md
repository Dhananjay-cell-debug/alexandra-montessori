# Nurseries index — heading and introduction section

## Intent and feel

Keep the current calm sand-coloured welcome: one confident title followed by warm, factual orientation. The editor should expose title and paragraphs separately while preserving their single public section.

## Current public section → exact WP canvas parity

- This is the first `<section className="bg-sand py-12 sm:py-16">` in `Nurseries.jsx`; it must remain one WP canvas section.
- Canvas layers mirror the existing DOM: centred `h1` (`Our Nurseries`) then the intro wrapper below it.
- Intro has three exact runtime branches: approved three records → two approved paragraphs; other non-zero records → two generated paragraphs using `Intl.ListFormat`; zero records → the single update paragraph.
- The approved-three copy names Hounslow, Heston, and Hammersmith and states 6 months–5 years for Hounslow/Heston versus 12 months–5 years for Hammersmith.

## Editable elements and controls

- Section: background token, vertical-space preset, content width, and reveal preset.
- Title: plain text, approved heading scale, colour token, alignment; it remains an `h1`.
- Intro: `Record-aware` or `Curated` mode, repeatable paragraph editor, token chips for count/names/age ranges, text measure, gap, and preview scenario selector.
- Show a live nursery-facts panel and warnings when authored names, age ranges, or counts disagree with published records.

## Layers and dragging

- Exact tree: `Heading & introduction section` → `Page title`; `Introduction` → `Paragraph 1`, `Paragraph 2` as resolved.
- Paragraphs can reorder inside `Introduction`; the title is locked first. Header and intro cannot be split into fabricated public sections.
- On-canvas text selection and the inspector target the same stored block; token chips can move within text by keyboard.

## Responsive behaviour

- Preserve current 4xl→5xl title scale, `py-12`→`sm:py-16`, centred alignment, and maximum readable intro width as default.
- Mobile allows natural title/paragraph wrapping and smaller gaps; factual content cannot be hidden per device.
- Scenario preview covers zero, one, current three, and future four-plus records without changing data.

## Record/template binding and overrides

- Section presentation and authored text live at `page:nurseries.sections.headingIntro`.
- In record-aware mode, count, name list, active status, ages, and hours resolve from the nursery collection; curated text is an explicit page override, not a mutation of branch records.
- Page title can inherit the WordPress post title; clearing its override resumes inheritance.

## Protected rules

- Exactly one `h1`; no raw HTML, arbitrary classes, scripts, or unsafe URLs.
- Publishing a curated factual conflict requires an authorised acknowledgement; route and nursery record values remain unchanged.
- The zero-record paragraph is a state variant, not a manually reorderable normal paragraph.

## Accessibility

- Heading hierarchy and paragraph DOM order follow the visual layer order.
- Tokenized/generated text resolves to natural prose for assistive technology; contrast must pass on the selected sand/background token.
- All drag operations have labelled move-up/move-down and announced position equivalents.

## Empty and error states

- Zero published records shows the current update sentence.
- A collection load error is not treated as zero: use the last published facts snapshot in public output and show an editor-only stale-data warning.
- Empty title blocks publish; empty curated intro offers the current record-aware output instead of silently publishing a blank band.

## Storage and versioning

- Store tokens, layout settings, mode, paragraph AST, responsive overrides, and last-resolved facts under `pages/nurseries/sections/heading-intro`.
- Revision comparison separates authored edits from changes caused by nursery data; restore can target content or presentation.

## Planned implementation files (future only)

- `am-visual-builder/schema/pages/nurseries/heading-intro.php`
- `am-visual-builder/admin/pages/nurseries/HeadingIntroEditor.jsx`
- `am-visual-builder/admin/components/NurseryFactDiagnostics.jsx`
- `am-visual-builder/runtime/pages/nurseries/HeadingIntroSection.php`

## Acceptance checklist

- [ ] Default output matches the single current sand section exactly.
- [ ] Current three-record and differing-age copy resolves correctly.
- [ ] Header and intro remain one public section but separately editable layers.
- [ ] Zero, one, future-many, and data-error previews are correct.
- [ ] One `h1`, contrast, DOM order, keyboard reorder, reset, and restore pass.

