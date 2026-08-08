# Funded Childcare — why choose Alexandra Montessori section plan

## Intent and feel

Reassure parents that funded places receive the same Montessori care, using a balanced, calm benefit grid rather than sales-heavy claims.

## Exact current JSX and public evidence

- Third content section is a contained white section with `py-16 sm:py-20`.
- Centred heading: eyebrow `Why choose Alexandra Montessori?`, h2 `Funded hours, full Montessori experience`.
- Four `fundingFeatures` cards render in exact order:
  1. Sprout — `Montessori curriculum` — `Funded hours, full Montessori experience - no compromise on quality.`
  2. Users — `Qualified staff` — `Warm, highly trained practitioners caring for your child.`
  3. Clock — `Flexible hours` — `Stretch your funded hours across the year to suit your family.`
  4. HeartHandshake — `Inclusive community` — `A diverse, welcoming setting where every family belongs.`
- Grid is one column by default, two at `sm`, four at `lg`; cards are centred with 16x16 icon badges.

## Current public section -> exact WP canvas parity

| Current JSX/public layer | Exact seeded WordPress canvas layer |
| --- | --- |
| One white contained section | One `Why choose us` section with current padding |
| Centred SectionHeading | `Heading group` with exact eyebrow and h2 |
| Four mapped benefits | Page-owned `Benefit collection` with four stable ordered records |
| Icon badge, h3, paragraph | Fixed flow layers inside each card |
| 1/2/4 grid | Breakpoint defaults matching current JSX |

## Exact editing controls

- Eyebrow/h2, section background/spacing/width and reveal preset.
- Benefit title, safe body copy, approved icon, add/duplicate/archive/reorder, active status and factual review note.
- Card surface, padding, alignment, equal-height behaviour, icon-badge size/colour, columns and gap.
- Optional item link is disabled by default; enabling it is a visible card-behaviour change with internal/external URL validation.

## Layers, reorder and dragging

- Exact tree: `Why choose us section` → `Heading group`; `Benefit grid` → four `Benefit card` → `Icon badge`, `Title`, `Text`.
- Pointer/keyboard drag reorders benefit cards only. Icon/title/text stay in protected flow order and cannot become free-positioned layers.
- Canvas selection edits the correct stable item rather than its array index.

## Responsive behaviour

- Preserve 1/2/4 columns, centred text, natural card height and full copy.
- Long headings/copy wrap without truncation; at 200% zoom cards reflow and no icon overlaps text.
- DOM order always matches the configured visual order.

## Data ownership and override semantics

- Benefits are owned by `page:funded-childcare.sections.whyChooseUs`; they are not inferred from nursery feature lists.
- Shared card/style tokens may be inherited, while content/icon/order remain page-local.
- Clearing a local style override resumes pattern styling without overwriting the four benefit records.

## Protected behaviour

- Stable IDs, non-empty title/body for visible cards, approved icons and safe text/URLs only.
- Claims about staffing, flexibility, inclusion and curriculum require a reviewer/date when materially edited.
- A hidden card is a reversible status change; removal does not silently renumber or copy content into another card.

## Accessibility

- h2→h3 structure and DOM order remain logical.
- Icons are decorative because the title carries meaning; text remains complete without icons or colour.
- Keyboard reorder announces position; contrast, focus (if links enabled) and reduced-motion reveal pass.

## Empty, loading and error states

- Current zero-item JSX would leave the section heading and empty grid; editor shows an internal warning and blocks accidental empty publication unless the section is intentionally disabled.
- Missing icon uses a neutral editor/public-safe fallback while retaining title/text. Missing title/body keeps item draft-only.
- Collection load failure uses the last moderated published benefit snapshot.

## Storage and versioning

- Store at `pages/funded-childcare/sections/why-choose-us` with ordered stable items, icons, review metadata and sparse layout/style overrides.
- Diff additions, archives, moves, copy/icon changes and reviewer state separately.
- Restore is isolated from offerings and application steps.

## Planned implementation files (future only)

- `am-visual-builder/schema/pages/funded-childcare/why-choose-us.php`
- `am-visual-builder/admin/pages/funded-childcare/WhyChooseUsEditor.jsx`
- `am-visual-builder/admin/components/FundingBenefitCardEditor.jsx`
- `am-visual-builder/runtime/pages/funded-childcare/WhyChooseUsSection.php`

## Acceptance checklist

- [ ] Exact eyebrow/h2, four icons/titles/copy/order and 1/2/4 grid reproduce.
- [ ] Pointer and keyboard reorder persist by stable ID.
- [ ] Long copy, 200% zoom, missing icon/title and empty/load states are safe.
- [ ] Claim review, link validation, icon semantics, contrast, diff and isolated restore pass.
