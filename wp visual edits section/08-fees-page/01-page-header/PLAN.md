# Fees page header plan

## Intent and feel

The header should plainly state “Admissions & Fees” and immediately explain how to use branch fee sheets. Its clean white, centred treatment prevents financial information from feeling promotional or confusing.

## Exact current JSX/public evidence

- `Fees.jsx` passes H1 `Admissions & Fees` and intro `Choose your preferred nursery to view the latest fee sheet. Funding, session patterns and final invoices are always confirmed directly with the branch.`
- `PageHeader.jsx` renders only `title` and optional `intro`: white surface, centred container, Reveal H1 and Reveal paragraph.
- It does **not** render an eyebrow, breadcrumb, flourish, background media, icon or CTA; those props are not part of its API.
- Current spacing is `pt-12 sm:pt-16 pb-6`; H1 is 4xl/5xl and intro is base/lg, max width 3xl, `mt-5`.

## Editable controls

- Page-specific H1/intro direct editing with reset.
- Background token, bounded padding, intro max width, alignment and approved responsive type scale.
- Reveal enable/delay preview with reduced-motion fallback.
- Financial/source review hint for changed claims.
- No crumb/eyebrow/media/CTA control in current parity; adding one requires a separately approved component change.

## Layers, reordering and dragging

Protected flow is section -> H1 -> intro. Text remains in responsive document flow, not free-positioned. Header remains the first visible page-owned section before the cream fee body and cannot be dragged into a fee card.

## Desktop, tablet and mobile

Current alignment stays centred on all devices. Device controls are limited to padding, type scale and width within safe bounds. Test 320px, long intro, 400% zoom and real global-header clearance.

## Data ownership and bindings

Copy/presentation belong to Fees page; shared markup/fallback classes belong to `PageHeader`. Global header/navigation are referenced, not duplicated. Page identity can seed H1 but overrides are explicit.

## Protected behavior

- Preserve one visible H1 and correct heading order.
- No raw HTML/scripts or fee data inside intro.
- Do not imply every branch has the same fee, that calculator output is an invoice, or that funding applies automatically.
- Header order/landmark and global skip-link behavior remain unchanged.

## Accessibility

H1 and intro maintain semantic order, readable line length/contrast and safe wrapping. Reveal respects reduced motion. Empty H1 blocks publication; intro may be absent and then its element/margin are omitted.

## State previews and failure handling

Preview exact current, long H1/intro, empty intro, high zoom, reduced motion and narrow mobile. Invalid/empty H1 falls back to `Admissions & Fees`; missing intro does not reserve whitespace.

## Storage and versioning

Store a stable Fees header section record with shared component schema version and per-device style values. Revisions preserve current strings. Future `PageHeader` fields do not auto-enable unsupported visuals.

## Planned implementation files (future only)

- `src/components/PageHeader.jsx`
- `src/pages/Fees.jsx`
- `src/lib/pageSectionModel.js`
- `wordpress/mu-plugins/alexandra-visual-builder/assets/panels/fees-page-header.js`
- `wordpress/mu-plugins/alexandra-visual-builder/tests/fees-page-header.php`

## Current public section -> exact WP canvas parity

| Current public element | Exact WP canvas requirement |
| --- | --- |
| Plain white header | Same surface and `pt-12/sm:pt-16 pb-6` spacing |
| H1 `Admissions & Fees` | Same title, semantic level, type and Reveal behavior |
| Current branch-confirmation intro | Same exact text, width and responsive size |
| No crumb/eyebrow/flourish/media/CTA | Canvas must not invent/show these as existing output |

## Acceptance checklist

- [ ] Untouched header matches `PageHeader.jsx` exactly.
- [ ] One visible semantic H1 remains.
- [ ] No unsupported PageHeader fields appear in the editor.
- [ ] Intro edits trigger appropriate financial/source review.
- [ ] Mobile/zoom states do not clip or overlap global UI.
- [ ] Header edits leave SEO, feature image and fee records untouched.

