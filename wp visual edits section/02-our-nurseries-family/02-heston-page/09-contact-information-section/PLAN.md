# Heston — contact information section

## Intent and feel

Make Heston’s visit/call/email information impossible to confuse with the nearby Hounslow branch.

## Current public section → exact WP canvas parity

- Ninth section is the sage rounded three-item contact block.
- Exact values: `36 Springwell Road, Hounslow TW5 9EJ`; `0203 627 6707`; `heston@alexandramontessori.co.uk` with MapPin/Phone/Mail and current href helpers.

## Editable elements and controls

- Linked Heston fields, labels, formatting, icons, order, style/spacing, visibility, validation, test-action, and downstream-impact preview.

## Layers and dragging

- Exact tree: `Contact information section` → `Contact block` → `Visit`, `Call`, `Email` → `Icon`, `Label`, `Value/link`.
- Items reorder inside block only; values remain Heston-bound and cannot drag between records.

## Responsive behaviour

- Stack mobile/three columns at `sm`; long address/email wrap, focus remains visible, DOM equals visual order.

## Record/template binding and overrides

- Heston entity owns address/postcode/phone/email; labels/style inherit template. Display formatting does not mutate normalized values.

## Protected rules

- Valid phone/email/address, generated safe hrefs, stable Heston binding, no raw href/cross-record drop.

## Accessibility

- Descriptive links, labels, decorative icons, touch size, contrast, focus, and readable wrapping.

## Empty and error states

- Missing field creates admin incomplete state and hide/block choice, never dead anchor; helper failure falls back to plain text.

## Storage and versioning

- Facts at `nurseries/{hestonUuid}/contact`; presentation at `nurseries/{hestonUuid}/sections/contactInfo`; audit downstream acknowledgement and independent restore.

## Planned implementation files (future only)

- `am-visual-builder/schema/entities/nursery/contact.php`
- `am-visual-builder/admin/pages/nursery/sections/ContactInfoEditor.jsx`
- `am-visual-builder/runtime/nursery/ContactInfoSection.php`
- `am-visual-builder/content/nurseries/heston/contact.json`

## Acceptance checklist

- [ ] Exact Heston facts/links/layout reproduce with no Hounslow leakage.
- [ ] Validation, keyboard order, responsive wrap, missing/helper failure, audit, reset, and restore pass.

