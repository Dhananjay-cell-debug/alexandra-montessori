# Home trust and accreditations plan

## Intent and feel

The closing Home strip should be light, compact and factual: four trust signals that reassure without overclaiming. Clients can replace generic shield glyphs with approved marks and adjust labels, while the editor treats every accreditation as a verifiable record rather than decoration.

## Current evidence

- `Trust` renders `data-am-vb-region="home-trust"` on white with 64px desktop/tablet and 48px mobile padding.
- Current labels are Official Ofsted Reports, Montessori Approach, EYFS Curriculum and Food Hygiene Rated.
- With no uploaded logo, each uses a ShieldCheck glyph; wrappers default to 28px.
- Mobile rows use icon left/text right inside a max 26rem column; from `sm`, items become equal centred columns.
- Content width defaults to 896px desktop/tablet and 416px mobile; grid gap is 20px desktop/tablet, 24px mobile.
- Extras append via `collections.trust`, sanitized to 12; visual column count is capped at five.

## Editable elements and controls

- Section visibility, white/background token, padding, content width, gap and icon size per device.
- Per trust item: factual label, optional evidence/source note, approved logo upload/library choice, alt/decorative decision, fallback icon selection, scale/offset and visibility.
- Add, duplicate, reorder, archive/delete extra and restore the four core items.
- Verification status shown in admin (Unverified / Reviewed / Source linked); status is not automatically displayed publicly.
- Optional internal/external destination is out of current parity and remains off unless a later approved design adds linked marks.
- Preview transparent/dark logos, long labels, one/four/five+ items and missing media.

## Selection, layers and dragging

Each item is a locked logo/icon + label group. Collection drag reorders whole items; keyboard move alternatives are required. Media movement is bounded inside the icon wrapper with contain fit. Label/icon may receive small optical offsets, but mobile icon-left and desktop icon-above structures remain protected. No free overlap or arbitrary z-order.

## Desktop, tablet and mobile

Mobile exact parity is stacked two-column mini-rows (icon plus text). From 640px, the same items become centred columns, initially four. Device controls cover icon size, gap/content width and bounded offsets. Test 320px, five columns, 200% zoom and long labels; grid should wrap/reduce columns intentionally rather than shrink below readability.

## Data ownership and bindings

Home owns the displayed ordered trust collection and visual overrides. Claims may reference canonical nursery/compliance records, but this compact strip stores a label plus optional evidence reference—not copied regulatory content. Media attachments belong to WordPress. Core labels in `site.js` and ShieldCheck remain fallback values.

## Protected rules

- Do not publish unverifiable ratings/accreditation claims as fact without review cues.
- Uploaded logos retain aspect ratio and cannot be recoloured/cropped misleadingly.
- Clamp counts, icon size, offsets, content width and gaps.
- Empty labels are invalid for visible items; raw HTML/scripts are disallowed.
- Hiding all items removes the section rather than leaving white padding.

## Accessibility and failure states

Labels convey meaning without logos. Official logos get meaningful alt only when it adds information; otherwise they are decorative beside identical text. Focus is not needed unless links are later introduced. Missing uploads fall back to ShieldCheck without broken image UI. Invalid extra records remain repairable in editor and are omitted publicly. Evidence/source unavailability triggers an admin warning, not an invented public status.

## Storage and versioning

Normalize core/extras to stable trust-item IDs in a future Home schema while reading `trust-1..4` and extra keys. Store evidence references, attachment IDs and presentation separately. Revision history highlights claim wording and logo changes more prominently than spacing edits. Maintain current sanitized collection limits.

## Planned implementation files (future only)

- `src/components/home/HomeTrustStrip.jsx`
- `src/lib/trustModel.js`
- `wordpress/mu-plugins/alexandra-visual-builder/assets/panels/home-trust.js`
- `wordpress/mu-plugins/alexandra-visual-builder/tests/home-trust.php`
- `src/pages/Home.jsx`

## Current public section → exact WP canvas parity

| Current public trust item/section | WP canvas requirement |
| --- | --- |
| Official Ofsted Reports | Same first label and default ShieldCheck fallback |
| Montessori Approach | Same second label/order and icon treatment |
| EYFS Curriculum | Same third label/order and icon treatment |
| Food Hygiene Rated | Same fourth label/order and icon treatment |
| Mobile icon-left rows / `sm+` centred columns | Same exact responsive structure, widths, gaps and 28px icons |

## Acceptance checklist

- [ ] Untouched four-item strip matches public desktop/tablet/mobile exactly.
- [ ] Stable identities keep claim/logo/evidence together through reorder.
- [ ] Missing media falls back cleanly without losing label meaning.
- [ ] Claims receive factual-review safeguards and no invented status.
- [ ] Extra/empty counts reflow or hide without blank bands.
- [ ] Logo movement/size stays bounded and aspect-safe.
