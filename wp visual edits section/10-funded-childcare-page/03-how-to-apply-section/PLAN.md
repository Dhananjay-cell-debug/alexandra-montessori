# Funded Childcare — how to apply section plan

## Intent and feel

Turn the claim process into three calm, sequential actions. Editing should make sequence unmistakable and prevent number/order drift.

## Exact current JSX and public evidence

- Full-width second content section: relative, overflow-hidden, `bg-sand py-20` with an absolute `grid-bg` at 10% opacity.
- Centred heading: eyebrow `How to apply`, h2 `Three simple steps`.
- `fundingSteps` maps to three `Reveal` card divs in this exact order:
  1. ShieldCheck — `Check eligibility`.
  2. BookOpen — `Register with us`.
  3. CalendarHeart — `Claim your hours`.
- The displayed number is currently derived from `i + 1`; cards are cream, rounded-4xl, p-8 with shadow. Grid becomes three columns at `md`.
- Current JSX uses a `div` grid, not an ordered-list element.

## Current public section -> exact WP canvas parity

| Current JSX/public layer | Exact seeded WordPress canvas layer |
| --- | --- |
| Sand section and decorative grid | `How to apply section` → background colour and decorative pattern |
| Centred SectionHeading | `Heading group` → eyebrow and h2 with exact text |
| `fundingSteps.map` | Ordered editor collection seeded with the exact three steps |
| `i + 1` circles | Auto-number layer derived from current item position |
| Icon/title/text card | Per-step layers in that exact visual order |
| `md:grid-cols-3` | One column below medium, three from medium |

## Exact editing controls

- Section background token, pattern visibility/opacity, spacing, width and reveal preset.
- Eyebrow/h2 direct editing and approved alignment/type controls.
- Step add, duplicate, archive, reorder; approved icon; title; safe rich text; optional vetted internal/external help link; review metadata.
- Auto numbering on by default, with optional manual display label only behind a sequence-warning control.
- Card surface, radius, shadow, padding, icon/number size/colour, columns and gap.

## Layers, reorder and dragging

- Exact tree: `How to apply section` → `Grid texture`, `Heading group`; `Step grid` → three `Step card` → `Number`, `Icon`, `Title`, `Text`.
- Pointer or keyboard card movement updates stored order; automatic numbers recalculate immediately to 1, 2, 3.
- Internal number/icon/title/text order is protected. Pattern cannot be dragged above content, and cards cannot leave this section.

## Responsive behaviour

- Preserve one-column mobile and three-column `md`, with no forced two-column midpoint unless explicitly changed.
- Cards expand for long copy and keep number/icon together; no fixed heights or clipped steps at 200% zoom.
- Visual and DOM order remain identical at all breakpoints.

## Data ownership and override semantics

- Steps are page-owned at `page:funded-childcare.sections.applicationSteps`.
- Number is computed presentation state, not stored factual content; stable step IDs survive reorder.
- Card/pattern styles may inherit a section pattern; local overrides are sparse and revertible without touching step copy.

## Protected behaviour

- At least one valid step if visible; unique stable IDs; safe icon names/links; no raw HTML.
- Eligibility and documentation claims require funding-content review and effective date.
- Current default is auto-numbering; manual labels trigger duplicate/out-of-sequence validation.

## Accessibility

- Current public JSX is a div grid; planned WordPress renderer should use `ol/li` semantics while preserving exact visual parity, subject to implementation QA.
- Icons are decorative beside explicit titles; numbers and titles communicate sequence without colour.
- Keyboard reorder announces new position; decorative grid is hidden from assistive technology.

## Empty, loading and error states

- Current zero-step JSX would show heading with an empty grid; builder adds an editor-only warning and blocks accidental empty publication unless section is deliberately disabled.
- Missing icon retains number/title/text with an editor placeholder. Missing title keeps the step draft-only.
- Failed collection load uses last published steps and marks the preview stale.

## Storage and versioning

- Store `pages/funded-childcare/sections/how-to-apply` with ordered stable IDs, title/text/icon/review fields and sparse section/card styles.
- Do not persist derived numbers; revision diff shows moves and their renumbering consequence.
- Restore only this section and retain review audit.

## Planned implementation files (future only)

- `am-visual-builder/schema/pages/funded-childcare/how-to-apply.php`
- `am-visual-builder/admin/pages/funded-childcare/HowToApplyEditor.jsx`
- `am-visual-builder/admin/components/AutoNumberedStepSorter.jsx`
- `am-visual-builder/runtime/pages/funded-childcare/HowToApplySection.php`

## Acceptance checklist

- [ ] Exact sand/grid band, headings, three icons/titles/texts and 1/3-column layout reproduce.
- [ ] Current div-grid evidence is documented; semantic ol/li enhancement preserves visual parity.
- [ ] Pointer/keyboard reorder always renumbers automatically and keeps DOM order.
- [ ] Review, missing-icon/title, empty/load-error and manual-number validation pass.
- [ ] Zoom, contrast, reduced motion, diff and isolated restore pass.
