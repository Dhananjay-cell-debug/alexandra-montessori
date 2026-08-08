# Footer nursery contacts column plan

## Intent and feel

The centre expanded-footer column should give families immediate, accurate branch contact details. It must feel data-connected: editing a nursery’s canonical address or phone in its nursery record should flow here, while a carefully labelled footer display override remains possible.

## Current evidence

- Expanded `Footer.jsx` renders “Nursery contacts” and maps every `locations` record.
- Each block contains nursery name, address and linked phone; current branches are Hounslow, Heston and Hammersmith.
- Names, addresses and phone display values already have visual edit keys; phone href uses `telHref` unless overridden.
- The column is centred below `lg`, left-aligned at `lg`, with vertically separated branch blocks.
- `locations` is WordPress-authoritative when its versioned data contract is present and static only for standalone preview.

## Editable elements and controls

- Column heading, alignment/density preset and branch spacing.
- Automatic branch membership from published nursery records; optional hide-from-footer flag per branch without deleting it.
- For each branch: source name/address/phone, explicit display override fields, formatted phone preview and “Edit canonical nursery details” deep link.
- Branch order references the global nursery order by default; footer-specific curated order requires an explicit mode.
- Optional phone icon from the approved set; no invented photos/cards in this compact section.
- Preview source-sync, override, missing phone, long address and international phone-format states.

## Selection, layers and dragging

Branch blocks reorder via list handles only in curated mode. Within a block, name/address/phone order is fixed for comprehension. Text can be selected inline but is not freely moved. The whole column stays in the second desktop grid slot for exact parity; any future grid rearrangement belongs to a separate approved footer-layout change.

## Desktop, tablet and mobile

Desktop renders a compact left-aligned column. Tablet/mobile stack and centre the blocks, allowing addresses to wrap without unbroken overflow. Phone links retain 44px effective target size. Test one, three and many future nurseries; a longer list may use sensible flow, never an inaccessible fixed-height clip.

## Data ownership and bindings

Nursery record IDs are authoritative; canonical `name`, `address` and `phone` belong to the nursery page/settings workstream. Footer stores only membership/order/presentation and typed optional display overrides. Link href is derived from the validated canonical/override phone, never edited as an unrelated arbitrary URL.

## Protected rules

- A displayed phone and its `tel:` target must derive from the same record.
- Hiding/removing a footer block cannot delete or unpublish a nursery.
- Do not permit raw HTML in addresses or names.
- Keep visible branch names unique enough to distinguish destinations.
- Never expose empty anchor tags for missing phones.

## Accessibility and failure states

Each branch block has a clear heading or semantically strong name; phone links announce the branch context when needed. Addresses wrap naturally and are not encoded only in icons. Missing phone omits the link and shows an editor warning; missing address does the same independently. If no valid nurseries exist, hide this public column and reflow the footer, while the editor explains the upstream data problem.

## Storage and versioning

Store stable nursery references and optional override fields under `footer.nurseryContacts`. Migrate `footer-{slug}-*` values by matching current IDs, then preserve IDs through slug migrations. Revision diffs identify canonical-versus-display changes. Public payloads receive sanitized resolved records only.

## Planned implementation files (future only)

- `src/components/footer/FooterNurseryContacts.jsx`
- `src/lib/nurseryModel.js`
- `wordpress/mu-plugins/alexandra-visual-builder/assets/panels/footer-nursery-contacts.js`
- `wordpress/mu-plugins/alexandra-visual-builder/tests/footer-nursery-contacts.php`
- `src/components/Footer.jsx`

## Current public section → exact WP canvas parity

| Public element | WP canvas requirement |
| --- | --- |
| “Nursery contacts” heading | Same heading treatment and second-column placement |
| Hounslow, Heston, Hammersmith blocks | Same current source values and source order |
| Name → address → clickable phone | Same hierarchy, wrapping and `tel` behaviour |
| Centre mobile / left desktop alignment | Same responsive alignment and spacing |

## Acceptance checklist

- [ ] All three current branch records match the public footer exactly.
- [ ] Canonical source edits propagate without losing explicit display overrides.
- [ ] Phone display and click target can never diverge.
- [ ] Missing fields omit only their own UI and remain clearly flagged.
- [ ] Future branches can join by stable reference without code-position keys.
- [ ] Footer remains usable with long addresses and multiple branches.

