# Availability branch contact-support plan

## Intent and feel

The closing support section should offer a simple human fallback: each current nursery’s address and phone, followed by the general line and hours. Facts should remain visibly bound to canonical nursery/site settings.

## Exact current JSX/public evidence

- A separate white section follows the cream form body, with `py-12`.
- `container-wide` grid maps all `locations`, one column by default and three columns from `md`, gap 5.
- Each `Reveal` card is sage-50, sage border, rounded-2xl, padding 6; it contains H2 nursery name, address, then phone link with Phone icon.
- Phone href is built as `tel:` plus the current phone with whitespace removed.
- After the grid, centred sentence reads `You can also call {brand.phonePrimary} during {brand.hours}.` with linked primary phone.
- With zero locations, current code leaves an empty grid but still renders the general line; it has no dedicated empty-state card.

## Editable controls

- Section/card surface, padding, gap, radius, border and exact one/three-column behavior.
- Source-bound field visibility/patterns for name, address and phone; edits deep-link to the nursery record rather than copy facts locally.
- General sentence token pattern with protected `{primaryPhone}` and `{hours}`.
- Card order/filter based on stable nursery references and previews for one/three/many/missing-field/zero records.
- Phone icon/style and Reveal timing within approved bounds.

## Layers, reordering and dragging

Each card is a grouped name -> address -> phone flow. Collection drag reorders stable nursery references and DOM order; keyboard moves match. Facts are not freely draggable or editable as page-local text. General line remains after the grid and cannot be dragged inside a branch card.

## Desktop, tablet and mobile

Current grid is stacked until `md`, then exactly three columns for three records. Future counts must wrap through an approved grid policy without shrinking cards illegibly. Test 320px, long address/phone, missing facts and 200% zoom.

## Data ownership and bindings

Branch name/address/phone come from WordPress-authoritative `locations`; primary phone/hours come from global `brand` settings. The page stores only presentation, membership/order and tokenized copy. Phone href must derive from the displayed validated phone, not a separate arbitrary URL.

## Protected behavior

- Hiding/removing a card does not delete or unpublish a nursery.
- Displayed phone and tel target cannot diverge.
- Missing phone produces no empty link; no `undefined` facts.
- Names/addresses do not accept raw HTML.
- General fallback remains reachable if no branch cards are valid.

## Accessibility

Branch H2s create a clear section outline. Phone links include branch context where needed and have visible focus/tap size. Address remains text, not icon-only. Reveal respects reduced motion. DOM and visual order stay aligned.

## State previews and failure handling

Preview exact three records, single/future branch, missing address/phone, long content and no locations. Current zero-location behavior is documented; planned safe empty handling can emphasize the general contact line without inventing a new public card unless approved. Source failures use last valid settings/fallbacks.

## Storage and versioning

Store stable nursery references and presentation under a versioned section key; global facts remain source-owned. Revision diffs describe membership/order/pattern changes. Do not snapshot whole nursery records into the page.

## Planned implementation files (future only)

- `src/components/availability/BranchContactSupport.jsx`
- `src/pages/Availability.jsx`
- `src/lib/nurseryModel.js`
- `wordpress/mu-plugins/alexandra-visual-builder/assets/panels/availability-branch-support.js`
- `wordpress/mu-plugins/alexandra-visual-builder/tests/availability-branch-support.php`

## Current public section -> exact WP canvas parity

| Current public element/layout | Exact WP canvas requirement |
| --- | --- |
| White `py-12` closing section | Same surface and placement after form body |
| One card per `locations` record | Same current branch order and canonical facts |
| Name, address, phone+Phone icon | Same hierarchy, styles and derived tel target |
| Stacked -> `md:grid-cols-3` | Same current responsive grid |
| General phone/hours sentence after grid | Same token-resolved copy and linked phone |

## Acceptance checklist

- [ ] Current three branch cards/general line match public data exactly.
- [ ] Canonical nursery/global changes propagate without duplicate facts.
- [ ] Visual reorder updates DOM while preserving stable bindings.
- [ ] Missing fields never create dead links or `undefined` text.
- [ ] Zero records retains a safe real contact path.
- [ ] Mobile/zoom/long-address states remain accessible.

