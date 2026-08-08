# Hammersmith — contact information section

## Intent and feel

Make Dalling Road, Hammersmith phone, and email instantly trustworthy and immune to cross-branch copy errors.

## Current public section → exact WP canvas parity

- Current sage rounded contact section contains Visit/Call/Email in three columns.
- Exact values: `Dalling Road, London W6 0EU`; `0204 618 3477`; `hammersmith@alexandramontessori.co.uk`, with current icons and href helpers.

## Editable elements and controls

- Linked Hammersmith facts, display format, labels/icons/order, tokens/spacing/visibility, validators, safe test action and downstream preview.

## Layers and dragging

- Exact tree: `Contact information section` → `Contact block` → Visit/Call/Email → `Icon`, `Label`, `Value/link`.
- Item reorder only; Hammersmith values cannot be dragged/rebound.

## Responsive behaviour

- Stack mobile/three columns `sm`; address/email wrap and focus visible; DOM/visual order equal.

## Record/template binding and overrides

- Hammersmith owns address/postcode/phone/email; labels/style inherited. Display-only formatting keeps normalized fact intact.

## Protected rules

- Valid facts/generated safe hrefs/stable record, no raw href or cross-record drop.

## Accessibility

- Descriptive link context, explicit labels, decorative icons, touch size, contrast, focus, readable email wrapping.

## Empty and error states

- Missing facts block/hide item, never dead link; helper failure renders safe plain text with diagnostic.

## Storage and versioning

- Facts `nurseries/{hammersmithUuid}/contact`; presentation `.../sections/contactInfo`; downstream audit and independent restore.

## Planned implementation files (future only)

- `am-visual-builder/schema/entities/nursery/contact.php`
- `am-visual-builder/admin/pages/nursery/sections/ContactInfoEditor.jsx`
- `am-visual-builder/runtime/nursery/ContactInfoSection.php`
- `am-visual-builder/content/nurseries/hammersmith/contact.json`

## Acceptance checklist

- [ ] Exact Hammersmith values/links/layout reproduce with no other-branch leakage.
- [ ] Validation, order, responsive wrap/focus, missing/helper error, audit/reset/restore pass.

