# Food & Hygiene — branch rating card grid plan

## Intent and feel

Show every branch’s source facts side by side with equal visual dignity, while making the difference between a numeric rating and an awaiting-listing record unmistakable.

## Exact current JSX and public evidence

- This is the page’s second and final public `<section>`: `container-wide pb-16 pt-6`.
- Inside is one `grid gap-6 lg:grid-cols-3`, mapping `foodHygieneRatings` derived from all `locations` in record order.
- Current record facts:
  - Hounslow — rating `4`; Ved Court, Alexandra Road, Hounslow TW3 1LS; 6 October 2025; London Borough of Hounslow; branch-specific FSA URL.
  - Heston — rating `5`; 36 Springwell Road, Hounslow TW5 9EJ; 20 January 2025; London Borough of Hounslow; branch-specific FSA URL.
  - Hammersmith — non-numeric `Check current local authority record`; Dalling Road, London W6 0EU; `Awaiting public FHRS listing`; London Borough of Hammersmith & Fulham; generic `https://ratings.food.gov.uk/` URL.
- Each card is one `Reveal` with gradient body and footer. Because all three current `href` values are truthy, **all three currently render `View public record`**, including Hammersmith’s generic FSA homepage.

## Current public section -> exact WP canvas parity

| Current JSX/public layer | Exact seeded WordPress canvas layer |
| --- | --- |
| One contained section | One `Branch rating grid` section only |
| `foodHygieneRatings.map` | Bound published-nursery collection in Hounslow, Heston, Hammersmith order |
| One/three-column grid | 1 column below `lg`, 3 from `lg` |
| Reveal card | Bound `Branch rating card` instance with current delay/order |
| Gradient body | Name, rating heading/badge, address and metadata sublayers |
| White footer | Conditional public-record link/message sublayer |
| Internal folders 03–06 | Editors for this card/section, **not additional public sections** |

## Exact editing controls

- Collection source/status filter, display order, optional manual subset and auto-include-new-branch control.
- Section padding/width, columns/gap per breakpoint; card equal height, surface, gradient, border/radius/shadow and reveal timing.
- Field visibility for branch name, rating heading/badge, address, rating date, authority and footer action.
- Selecting a fact deep-links to that nursery’s Food Hygiene record fields; static labels/style remain template controls.
- State preview for Hounslow 4, Heston 5, Hammersmith pending, missing fields and future branch.

## Layers, reorder and dragging

- Exact tree: `Branch rating grid section` → `Bound nursery collection` → `Hounslow card`, `Heston card`, `Hammersmith card` → `Gradient body` → `Branch name`, `Rating group` → `Status heading`, `Badge`, `Address`, `Metadata list`; `Footer` → `Record action/message`.
- Card drag changes page display order only and stores stable nursery UUIDs; it cannot mutate global nursery order or transfer facts between records.
- Keyboard reorder mirrors drag and updates DOM order. Internal factual layer order is protected.

## Responsive behaviour

- Preserve one-column mobile/tablet and three columns from `lg`, equal-height flex cards and footer pinned by `mt-auto`.
- Rating badge remains min-16 and does not collide with long branch/status text; address/authority wrap without overflow.
- DOM order equals displayed order; no horizontal carousel or hidden factual rows by default.

## Data ownership and override semantics

- Rating, date, authority, address and URL remain owned by each stable nursery entity.
- Page owns collection display order/filter and shared card presentation only.
- A page-level field-display override never copies or edits factual values. New published nurseries may auto-join according to the explicit collection rule.
- Generic/static labels belong to the card template and can be overridden page-wide, not per factual record by default.

## Protected behaviour

- No direct canvas overwrite of ratings/dates/authority/address; selecting them opens the source record with downstream-impact warning.
- Rating claims need source URL/checker/check date; low/pending values cannot be hidden or restyled to imply a 5 without conspicuous governance warning.
- Stable slugs/UUIDs and record association cannot be changed by drag/drop.

## Accessibility

- Each card’s h2 and branch label create understandable context; rating meaning appears as text, not colour alone.
- `dl/dt/dd` metadata semantics, visible external-link focus, keyboard reorder and 200% zoom wrapping are required.
- Reveal respects reduced-motion and grid/DOM order remain aligned.

## Empty, loading and error states

- Current zero-record JSX renders the padded section with an empty grid and no public message; builder shows an editor-only diagnostic rather than inventing a new section/message.
- Failed record load uses last published verified collection snapshot and marks it stale; it must not masquerade as zero nurseries.
- A malformed branch card renders the template’s pending/incomplete states without `undefined` and is blocked from publication if required identity is missing.

## Storage and versioning

- Store layout/filter/order at `pages/food-hygiene/sections/rating-grid` using stable nursery UUID references.
- Factual history stays on `nurseries/{uuid}/food-hygiene` with source/check metadata; publish snapshot records entity revisions used.
- Diff separates page ordering/style from upstream record changes; page restore cannot roll back regulated source facts.

## Planned implementation files (future only)

- `am-visual-builder/schema/pages/food-hygiene/rating-grid.php`
- `am-visual-builder/admin/pages/food-hygiene/RatingGridEditor.jsx`
- `am-visual-builder/admin/components/BoundHygieneCard.jsx`
- `am-visual-builder/runtime/pages/food-hygiene/RatingGridSection.php`

## Acceptance checklist

- [ ] One public section and exact Hounslow/Heston/Hammersmith order/facts reproduce.
- [ ] Hammersmith correctly shows pending badge **and current truthy generic-link action**.
- [ ] 1/3-column layout, equal cards, footer pinning, wrapping and reduced motion pass.
- [ ] Drag/keyboard order changes display only; no cross-record mutation occurs.
- [ ] Zero/load/malformed states, source governance, diff and isolated restore pass.
