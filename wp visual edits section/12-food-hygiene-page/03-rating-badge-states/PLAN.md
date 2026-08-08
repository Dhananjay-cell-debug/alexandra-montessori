# Food & Hygiene — rating badge states plan

## Intent and feel

Make a verified numeric score immediately legible while treating unavailable/non-numeric data cautiously. Colour supports the status but never becomes the only meaning.

## Exact current JSX and public evidence

- `getRatingBadge(rating)` currently tests the value with `/^\d+$/`; the public classification is string parsing, not a separate structured status field.
- Any digit-only value returns badge label equal to the source value and heading `FHRS rating {rating}`.
- Only exact string `5` gets dark sage `bg-sage-600 text-white`; every other digit-only value gets light sage `bg-sage-100 text-sage-800`.
- Any non-numeric, empty, missing or malformed value resolves to badge `Check latest record`, amber tone and heading `Awaiting public listing`.
- Current results: Hounslow 4/light sage; Heston 5/dark sage; Hammersmith pending/amber.

## Current public section -> exact WP canvas parity

| Current internal state | Exact seeded WordPress card output |
| --- | --- |
| Rating `5` | Heading `FHRS rating 5`; dark sage badge `5` |
| Rating `4` | Heading `FHRS rating 4`; light sage badge `4` |
| Other digit-only string | `FHRS rating {value}`; light sage badge |
| Hammersmith non-numeric string | `Awaiting public listing`; amber `Check latest record` badge |
| Empty/missing/malformed | Same current pending output, not a blank badge |
| Folder scope | Internal `Rating group` editor within the one card-grid section; no new `<section>` |

## Exact editing controls

- State preview selector with current fixtures: 5, 4, 0–3, other numeric, non-numeric, empty and malformed.
- Static heading pattern, pending heading/label, badge min-size, padding, radius, type scale, alignment and approved semantic tone mapping.
- Record value is displayed read-only in canvas with `Edit source record` deep link and source/check metadata.
- A planned structured `status` migration can be previewed beside current parser result but cannot change public seed until approved.

## Layers, reorder and dragging

- Exact internal tree: `Rating group` → `Status heading`, `Rating badge` inside each bound branch card.
- Heading and badge may not swap because current flex layout presents contextual heading first in DOM and badge second.
- Badge is not freely draggable/recolourable; approved size/tone controls are inspector values. State previews do not reorder branch cards.

## Responsive behaviour

- Preserve `flex items-start justify-between gap-4`, h2 at 2xl and badge min-16 with px-4.
- Long pending labels wrap within the badge and do not push heading/address outside viewport; stacked fallback may activate only at validated narrow widths while preserving DOM order.
- 200% zoom and text-only/high-contrast modes retain the full status.

## Data ownership and override semantics

- Actual rating value belongs to `nursery:{uuid}.hygieneRating` and is never stored as page static copy.
- Heading patterns/tone mapping belong to the Food Hygiene card template; page may hold a reviewed template override.
- Future structured status should be migrated with recorded parser→status mapping; until then exact current parser output is the seed.

## Protected behaviour

- Editors cannot recolour a low/pending rating to imitate rating 5 or type a display score independent of the source record.
- Tone changes require semantic contrast review and retain textual status.
- Invalid numeric range is flagged even though current regex accepts any digits; default public parity remains documented until source data is corrected.

## Accessibility

- Heading and badge both expose meaningful text; colour is supplemental.
- Avoid announcing the same score excessively: badge can be `aria-hidden` when h2 already provides the complete rating, or receive one concise accessible label after QA.
- Contrast passes for dark sage, light sage and amber; text reflow works at 200% zoom.

## Empty, loading and error states

- Empty/missing/malformed current source resolves to pending; admin distinguishes those causes rather than treating them as verified awaiting-listing.
- Source load failure uses the last verified published rating and stale warning, not an automatic pending downgrade.
- Missing tone/template data falls back to the exact current mapping and never removes status text.

## Storage and versioning

- Store template labels/tones/sizing at `pages/food-hygiene/card-template/rating-badge`; source rating/history stays on nursery entity.
- Save state-mapping version, reviewer, contrast result and structured-status migration provenance.
- Diff template changes separately from record rating changes; rollback never overwrites a newer source rating.

## Planned implementation files (future only)

- `am-visual-builder/schema/pages/food-hygiene/rating-badge.php`
- `am-visual-builder/admin/pages/food-hygiene/RatingBadgeStateEditor.jsx`
- `am-visual-builder/admin/components/HygieneStatePreview.jsx`
- `am-visual-builder/runtime/pages/food-hygiene/RatingBadge.php`

## Acceptance checklist

- [ ] Hounslow 4/light, Heston 5/dark and Hammersmith pending/amber exactly reproduce.
- [ ] Current regex classification is documented; no false claim that structured status already exists.
- [ ] Internal editor does not create an extra public section or permit free-position badge dragging.
- [ ] Missing/malformed/load-failure states are distinguished in admin and safe publicly.
- [ ] Text meaning, contrast, zoom, source ownership, mapping diff and rollback pass.
