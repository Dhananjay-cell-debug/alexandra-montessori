# Funded Childcare — funding offerings section plan

## Intent and feel

Present the four funding routes as a warm, image-led overview that parents can scan quickly, while giving editors strong freshness controls around eligibility claims.

## Exact current JSX and public evidence

- First content section after `PageHeader`: `<section className="container-wide pb-6 pt-2">`.
- Left-aligned `SectionHeading`: eyebrow `Our funded childcare offerings`, h2 `The funding on offer`.
- `funding` maps to four cards in this order:
  1. `Funded childcare from 9 months` — `funding-babies-outdoors.webp`.
  2. `15 hours free childcare` — `funding-sports-session.webp`.
  3. `30 hours free childcare` — `friends-two.webp`.
  4. `Two-year-old funding` — `classroom-light.webp`.
- Every card has a 4:3 image, fixed over-image `PoundSterling` badge, title and plain paragraph. Grid is 1 column, 2 at `sm`, 4 at `lg`.

## Current public section -> exact WP canvas parity

| Current JSX/public layer | Exact seeded WordPress canvas layer |
| --- | --- |
| One contained section | One `Funding offerings` section with current spacing |
| Left-aligned SectionHeading | `Heading group` → eyebrow and h2 with exact text |
| `funding.map(...)` | `Funding offering collection` with four stable ordered items |
| Image plus fixed Pound badge | Per-card `Media frame` → image and bounded currency badge |
| Title and paragraph | Per-card `Content` → h3 and body copy |
| 1/2/4 grid | Breakpoint defaults 1 mobile, 2 small/tablet, 4 large |

## Exact editing controls

- Section heading text/alignment, background, width, top/bottom spacing and reveal preset.
- Offering add, duplicate, archive/remove, manual order, title, safe rich text, image, alt, focal point, zoom, eligibility source URL, effective/review date and status.
- Grid columns/gaps by breakpoint; card surface, radius, shadow, image ratio, content padding and hover token.
- Pound badge icon, size, colour, inset position and visibility using bounded approved controls; current fixed `PoundSterling` is the seed.

## Layers, reorder and dragging

- Exact tree: `Funding offerings section` → `Heading group`; `Offering grid` → four `Offering card` → `Media frame` → `Image`, `Currency badge`; `Content` → `Title`, `Text`.
- Dragging a card changes only page-owned offering order; keyboard move-up/down produces the same order and announces position.
- Image focal point is draggable within its frame. Currency badge stays bounded to the media frame; title/text remain flow layers and cannot be freely overlaid.

## Responsive behaviour

- Preserve 1/2/4 columns, 4:3 media and equal-height flex cards as default.
- Mobile cards remain one-column with readable full copy; no horizontal carousel or truncated eligibility text.
- Per-device focal overrides are sparse; all card content remains available at 200% zoom and long titles grow naturally.

## Data ownership and override semantics

- Offering collection is owned by `page:funded-childcare.sections.offerings`, not nursery records.
- Media references WordPress attachment IDs; eligibility copy, source and review metadata live on each offering item.
- Shared card presentation may inherit a page pattern, while each item stores content/media only; a local style override is visibly labelled and reversible.

## Protected behaviour

- Stable item IDs, at least one valid visible card, safe media MIME/size, required informative alt and sanitized links.
- Changes to age, hours, weeks, working-parent or benefits criteria require named reviewer/date and cannot publish expired claims silently.
- Removing a card is confirmation-gated and does not delete its media-library asset.

## Accessibility

- h2→h3 hierarchy, meaningful image alt, logical collection order and sufficient badge/text contrast.
- Pound icon is decorative because titles convey meaning; it is hidden from assistive technology.
- Pointer drag has keyboard equivalents; hover scaling respects reduced-motion.

## Empty, loading and error states

- Current JSX with zero items would leave heading plus an empty grid; WP seed preserves normal output and shows an **editor-only** zero-item warning rather than inventing public contact content.
- A deliberate same-section empty message may be authored later as a public change, but is off by default.
- Broken image uses last published attachment then approved placeholder; incomplete new cards remain draft-only.

## Storage and versioning

- Store at `pages/funded-childcare/sections/offerings` with ordered stable IDs, item content/review metadata, attachment/crop data and sparse layout overrides.
- Diff shows moves, claim edits, status/review changes and crop thumbnails separately.
- Restore this section without rolling back steps or other page sections.

## Planned implementation files (future only)

- `am-visual-builder/schema/pages/funded-childcare/offerings.php`
- `am-visual-builder/admin/pages/funded-childcare/OfferingsEditor.jsx`
- `am-visual-builder/admin/components/FundingOfferingCardEditor.jsx`
- `am-visual-builder/runtime/pages/funded-childcare/OfferingsSection.php`

## Acceptance checklist

- [ ] Exact heading, four titles/images/copy/order and 1/2/4 grid reproduce.
- [ ] Currency badge remains correctly bounded over each 4:3 image.
- [ ] Pointer/keyboard reorder, upload/focal crop, responsive preview and direct editing work.
- [ ] Claim review, expired/incomplete/broken-image and zero-item states are safe and honest.
- [ ] Heading hierarchy, contrast, reduced motion, diff and isolated restore pass.
