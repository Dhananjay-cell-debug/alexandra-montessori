# Home testimonial cards plan

## Intent and feel

Each testimonial card should feel like an authentic parent voice: warm sand surface, restrained quotation mark, readable title and quote, then a clear parent/location attribution. The client should edit or choose real approved testimonials without cross-wiring identities when cards move.

## Current evidence

- Home resolves `cmsCollection("testimonials", staticTestimonials)` and renders only `.slice(0, 3)`.
- Cards form one column initially, two from `md`, and three from `lg`.
- Each sand/95 card is rounded, shadowed and uses a full-card editable frame plus independently editable quote icon, title, quote, name and location.
- Current first three are “An outstanding nursery” (Hammersmith), “Highly recommend” (Heston), and “A calm, confident start” (Hounslow).
- The default Lucide Quote icon can be replaced per card; body copy wraps long/unbroken text and cards hide overflow.
- Existing Home overrides are position-based `testimonial-1..3`, which risks mismatch if CMS ordering changes.

## Editable elements and controls

- Featured-testimonial picker: choose/order up to three approved CMS records; show source status and full archive link.
- Per card Home display fields: title, quote, parent attribution, location and explicit source/consent note; allow override/reset without silently changing the canonical testimonial.
- Quote-icon approved-library/custom upload, alt/decorative setting, contain fit and bounded scale/offset.
- Whole-card frame scale/offset per device, surface/radius/padding preset and vertical alignment, constrained to the grid.
- Card actions: reorder featured cards, replace with another approved record, unfeature, and deep-link to edit canonical testimonial.
- Quality checks: excessive quote length, missing attribution/location, duplicated card and potentially identifying child information warning.

## Selection, layers and dragging

Each card is a grouped frame; dragging empty frame space reorders the featured collection or applies bounded optical offset only after explicit mode choice. Internal layers stay icon → title → quote → attribution. Text layers can be selected/edit/reset independently but cannot be dragged outside the card. Reordering moves the stable testimonial reference plus all Home overrides together.

## Desktop, tablet and mobile

Grid parity is 1 / 2 from 768px / 3 from 1024px. Whole-card offsets/scales are device-specific but cannot overlap adjacent cards. Cards may have natural unequal height on two-column tablet; desktop aims for harmonious height without truncating quotes. Test long copy, 320px width, 200% zoom and one/two/three cards.

## Data ownership and bindings

Canonical title/quote/name/location live in the Testimonials CMS collection. Home stores ordered stable testimonial IDs plus explicit display overrides and visual frame/icon settings. Static testimonials remain a standalone-build fallback only. The stage section owns background/heading/CTA and controls whether empty data hides the full section.

## Protected rules

- Do not fabricate reviews, ratings or parent identities.
- A source change cannot retain another source’s name/location override without a confirmation/reset step.
- No quote truncation that changes meaning; use layout/selection guidance instead.
- Clamp frame movement/scale and preserve readable font sizes/card padding.
- Avoid raw HTML/scripts and ensure private/unapproved testimonial records cannot be featured publicly.

## Accessibility and failure states

Use semantic quotation/figure structure where practical; the quote icon is decorative unless uniquely meaningful. Attribution must remain associated with its quote. Long text wraps and remains fully readable. Missing icon falls back to the current Quote glyph. Missing/deleted source shows an orphan repair card in admin and is omitted publicly; fewer cards reflow. Zero valid cards triggers the stage’s deliberate empty state.

## Storage and versioning

Migrate position-based first-three overrides to stable testimonial IDs using the current CMS/static ordering and save a migration audit. Version featured references separately from canonical records. Revisions show which testimonial was featured/unfeatured/reordered and which fields are Home-only overrides. Never copy full CMS records unnecessarily into the Home option.

## Planned implementation files (future only)

- `src/components/home/HomeTestimonialCards.jsx`
- `src/lib/testimonialModel.js`
- `wordpress/mu-plugins/alexandra-visual-builder/assets/panels/home-testimonial-cards.js`
- `wordpress/mu-plugins/alexandra-visual-builder/tests/home-testimonial-cards.php`
- `src/pages/Home.jsx`

## Current public section → exact WP canvas parity

| Current public card | WP canvas requirement |
| --- | --- |
| An outstanding nursery — Parent of a 3-year-old, Hammersmith | Same resolved first record, full quote and card layers |
| Highly recommend — Parent of a 4-year-old, Heston | Same resolved second record, full quote and card layers |
| A calm, confident start — Parent of a 2-year-old, Hounslow | Same resolved third record, full quote and card layers |
| Sand rounded cards with quote glyph and divider | Same surface, padding, shadow, icon and attribution border |
| 1 / 2 / 3 responsive grid | Same breakpoints, gap (from stage) and no truncation |

## Acceptance checklist

- [ ] Current first three cards match public source values and visual structure exactly.
- [ ] Stable source IDs keep title/quote/name/location/icon/frame together through reorder.
- [ ] Canonical versus Home override is visible and reversible.
- [ ] Long, missing and deleted-source cases reflow without false attribution.
- [ ] Review authenticity/consent safeguards are present.
- [ ] Card edits never change stage background/heading/CTA.

