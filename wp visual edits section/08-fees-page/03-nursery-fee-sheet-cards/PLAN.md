# Nursery fee-sheet cards plan

## Intent and feel

Each card should make the correct branch document obvious and easy to download while keeping age range and source ownership trustworthy. Clients edit canonical nursery/PDF facts at their source, not by pasting divergent URLs into individual buttons.

## Exact current JSX/public evidence

- `feeSheets` is derived from `locations`, filtering only records with a non-empty `feeSheetPdf`, then mapping `{name, href}`.
- The right column starts with a `grid gap-5` of Reveal cards; missing-PDF nurseries are currently omitted entirely.
- Each card is white, sage border, rounded-2xl, soft shadow, `p-5 sm:p-6`.
- Inside is stacked flex then `sm` horizontal/centred/space-between.
- H2 is `{sheet.name} fees`; age line finds a location by matching `location.name === sheet.name`, then uses ageRange or `Babies to 5 Years`.
- Primary anchor reads `Download PDF` plus FileDown, opens `sheet.href` in a new tab with `noopener noreferrer`.
- Current code has no explicit empty-state message if all fee sheets are absent.

## Editable controls

- Card title/age/action token patterns, approved FileDown icon and PDF/new-tab cue.
- Surface, border, radius, shadow, padding, gap and exact stacked/`sm` horizontal layout.
- Ordered membership derived from valid published nursery records; source-edit shortcuts for age range/PDF.
- Preview valid card, long name/age, missing PDF, invalid document, one/three/many and zero cards.
- Optional file metadata may appear only when it is actually resolved and rendered through an approved schema change.

## Layers, reordering and dragging

Each card is a grouped text block + download action; collection drag moves the stable nursery/PDF record and DOM order together. Internal H2/age/action remain responsive flow and cannot overlap. Cards remain in the right column before the funding callout; arbitrary absolute movement/full-width relocation is not current parity.

## Desktop, tablet and mobile

The page stacks photo then right column below `lg`; at `lg` cards use the 1.08fr right column. Each card is vertical on mobile and horizontal from `sm`, with download full width then auto. Test 320px, long filenames/names, zoom and several future branches.

## Data ownership and bindings

Canonical nursery name/age range/fee PDF belong to `locations`/Nursery CMS records. Fees page stores presentation, query/order and tokenized copy only. Current name-based age lookup is a fragility: planned normalization should carry stable nursery ID through `feeSheets` rather than infer identity by display name.

## Protected behavior

- PDF target remains bound to the matching nursery record and validated as an allowed document/HTTPS URL.
- Missing PDF produces no empty clickable anchor; current behavior omits that card.
- External/new-tab downloads retain `noopener noreferrer` and an accessible PDF/new-window cue.
- Reordering/hiding a card cannot modify/unpublish its nursery or delete the PDF attachment.
- Financial labels cannot imply the PDF is current without an actual version/update source.

## Accessibility

Each H2 names its card; action accessible name should include branch/PDF context even if visible label remains `Download PDF`. Focus is visible and touch target sufficient. File type/new-tab is conveyed textually or accessibly, not by FileDown alone. DOM order follows visual order.

## State previews and failure handling

Preview exact current cards, missing one PDF (card omitted), invalid URL, missing age (current fallback), zero sheets and slow document validation. The editor shows source warnings for omitted/incomplete branches. A future inline zero-state must be approved within this same section; it must not be presented as current output.

## Storage and versioning

Store stable nursery IDs/query/order and card presentation in a versioned section record; PDFs remain source-owned. Migrate current name/href mapping to IDs with an audit. Revisions identify membership/order/copy/style changes and never copy binary PDFs into page JSON.

## Planned implementation files (future only)

- `src/components/fees/FeeSheetCards.jsx`
- `src/pages/Fees.jsx`
- `src/data/site.js` (stable-ID `feeSheets` fallback contract)
- `src/lib/nurseryModel.js`
- `wordpress/mu-plugins/alexandra-visual-builder/assets/panels/fees-sheet-cards.js`
- `wordpress/mu-plugins/alexandra-visual-builder/tests/fees-sheet-cards.php`

## Current public section -> exact WP canvas parity

| Current public behavior | Exact WP canvas requirement |
| --- | --- |
| Cards only for non-empty `feeSheetPdf` | Same current filter/omission behavior |
| `{name} fees` + resolved age/fallback | Same text/source and current fallback rule |
| White bordered soft-shadow card | Same frame/padding and right-column sequence |
| Mobile stacked / `sm` horizontal | Same exact flex and action width behavior |
| `Download PDF` new-tab safe anchor | Same label, FileDown and security attributes |
| No current zero-state message | Canvas documents/admin-previews risk; does not invent public output |

## Acceptance checklist

- [ ] Current valid branch cards match JSX/data exactly.
- [ ] Stable-ID migration removes display-name identity risk.
- [ ] Missing/invalid PDFs never become dead anchors.
- [ ] Visual reorder preserves source binding and DOM order.
- [ ] PDF/new-tab purpose is accessible.
- [ ] Card edits do not mutate canonical nursery/PDF records.

