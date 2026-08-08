# Food & Hygiene — rating metadata plan

## Intent and feel

Make the evidence beneath each score easy to verify: where the branch is, when the record was dated and which authority owns it. Editing should feel factual and source-linked, not like ordinary marketing copy.

## Exact current JSX and public evidence

- This folder represents internal layers of each existing branch card, not a separate public section.
- Beneath the rating group, JSX renders `rating.address` in a paragraph.
- It then renders one `dl` with two rows: `Rating date` → `rating.ratingDate`; `Authority` → `rating.authority`.
- Current data is stored as display strings in `site.js`/nursery CMS fields, not as proven structured date objects:
  - Hounslow: `6 October 2025`; London Borough of Hounslow.
  - Heston: `20 January 2025`; London Borough of Hounslow.
  - Hammersmith: `Awaiting public FHRS listing`; London Borough of Hammersmith & Fulham.
- Address is copied from each nursery’s canonical address field.

## Current public section -> exact WP canvas parity

| Current internal layer | Exact seeded WordPress card parity |
| --- | --- |
| Address paragraph | Bound nursery address directly below rating group |
| Bordered `dl` | One `Rating metadata` definition list in the gradient body |
| Static `Rating date` dt | Same template label and bound display-string dd |
| Static `Authority` dt | Same template label and bound plain-text dd |
| Hammersmith date string | Exact `Awaiting public FHRS listing` value, not a fabricated calendar date |
| Folder scope | Internal card editor only; no new `<section>` or standalone metadata card |

## Exact editing controls

- Template labels, label/value typography, border/divider, top spacing, row gap and visibility for address/date/authority.
- Read-only bound value previews with `Edit Hounslow/Heston/Hammersmith source` deep links and downstream usage list.
- Source editor controls for display date, optional normalized ISO date, authority, canonical address, evidence URL, last-checked date and reviewer.
- Optional `Source last checked`/status rows are off by default and clearly labelled as new public fields.

## Layers, reorder and dragging

- Exact internal tree: `Card gradient body` → `Address`; `Rating metadata list` → `Rating date row` → `Label`, `Value`; `Authority row` → `Label`, `Value`.
- Metadata rows may reorder only through a labelled template control; default remains date then authority and DOM order follows.
- Factual values cannot be dragged between branches or converted to free text on the page. Internal layers cannot leave the parent rating card.

## Responsive behaviour

- Preserve one-column `dl`, `gap-4`, border-top and full-width wrapping at all breakpoints.
- Long authority/address strings wrap without horizontal scrolling or overlapping the badge/footer.
- 200% zoom expands card height; footer remains after metadata and no value is truncated.

## Data ownership and override semantics

- Address belongs to the nursery’s canonical contact/location record.
- Rating display date, authority, source/check metadata belong to `nursery:{uuid}.foodHygiene`.
- `Rating date`/`Authority` labels and metadata presentation belong to the Food Hygiene card template.
- A page display-format override never rewrites the source value; normalized-date migration must preserve the current display string and provenance.

## Protected behaviour

- No direct page-canvas mutation of regulated facts; source edit requires permission, evidence and downstream-impact acknowledgement.
- Authority is sanitized plain text; dates must not be fabricated or automatically parse ambiguous strings.
- Hammersmith’s awaiting-listing string must not be converted into today’s date or hidden as if inspected.

## Accessibility

- Preserve `dl/dt/dd` semantics regardless of visual styling or row order.
- Labels cannot be blank or communicated by position/colour alone.
- Screen-reader order matches visual order; wrapping, contrast and 200% zoom pass.

## Empty, loading and error states

- Current JSX would render empty paragraph/dd values if source strings are blank; the builder must show precise editor completeness errors and never output literal `undefined`.
- Exact current public fallback is no authored `Not yet available` label except Hammersmith’s stored display string. Any generic fallback is a deliberate template edit, off by default.
- Source-load failure uses last verified published values with stale warning; it is distinct from genuinely missing fields.

## Storage and versioning

- Store card-label/layout settings at `pages/food-hygiene/card-template/metadata`.
- Store factual values/evidence history at `nurseries/{uuid}/food-hygiene`; normalized date and original display string coexist during migration.
- Revision diff separates template label/order changes from source fact changes and records reviewer/source/check time.

## Planned implementation files (future only)

- `am-visual-builder/schema/pages/food-hygiene/rating-metadata.php`
- `am-visual-builder/schema/entities/nursery/food-hygiene.php`
- `am-visual-builder/admin/pages/food-hygiene/RatingMetadataEditor.jsx`
- `am-visual-builder/admin/components/BoundComplianceField.jsx`
- `am-visual-builder/runtime/pages/food-hygiene/RatingMetadata.php`

## Acceptance checklist

- [ ] All three current addresses, display-date strings and authorities reproduce exactly.
- [ ] Documentation does not falsely claim dates are already structured.
- [ ] Metadata remains inside the existing card/section and keeps date-before-authority order.
- [ ] Source ownership, permission, evidence, wrapping, dl semantics and missing/load states pass.
- [ ] Template versus fact diff, normalized-date provenance and rollback pass.
