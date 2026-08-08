# Hounslow — contact information section

## Intent and feel

Make the Hounslow visit/call/email strip an accurate, high-confidence action area with strong record ownership and typo-resistant controls.

## Current public section → exact WP canvas parity

- Ninth public section is a sage-500 rounded block with three columns: Visit us, Call us, Email us.
- Exact Hounslow values: `Ved Court, Alexandra Road, Hounslow TW3 1LS`; `0208 001 5165`; `info@alexandramontessori.co.uk`.
- Icons are MapPin, Phone, Mail; phone uses normalized `tel:` and email uses Gmail helper in a new safe tab.

## Editable elements and controls

- Linked address/phone/email fields, display formatting, labels, approved icons, column order, background/text tokens, padding, and visibility with completeness diagnostics.
- Address autocomplete is advisory only; telephone/email validators and one-click test actions use the unsaved preview value safely.

## Layers and dragging

- Exact tree: `Contact information section` → `Contact block` → `Visit item`, `Call item`, `Email item` → `Icon`, `Label`, `Value/link`.
- Items can reorder within the block by pointer/keyboard; field values remain bound to Hounslow and cannot be dragged between items/records.

## Responsive behaviour

- Stack on mobile and use three columns from `sm`; long address/email wraps, with no overflow or clipped focus ring.
- DOM order equals configured visual order on every device.

## Record/template binding and overrides

- Hounslow entity owns address, postcode, phone, and email; section labels/style inherit template.
- Display-only formatting overrides never alter normalized contact fields; changing a factual field warns about every Hounslow consumer before save.

## Protected rules

- Valid UK phone/email, non-empty address, safe external rel, generated hrefs, and stable Hounslow binding.
- Raw href entry and cross-record copy by dragging are prohibited.

## Accessibility

- Links have descriptive context, visible focus, adequate contrast, and usable touch size; `break-all` behaviour must not impair screen-reader text.
- Icons are decorative beside explicit labels.

## Empty and error states

- Missing field shows an admin incomplete item and blocks publish or explicitly hides that item; no dead anchors.
- If helper generation fails, public output falls back to safe plain text and logs the Hounslow field error.

## Storage and versioning

- Facts version on `nurseries/{hounslowUuid}/contact`; presentation/order at `nurseries/{hounslowUuid}/sections/contactInfo`.
- Audit shows downstream-impact acknowledgement; restore can target fact or presentation revision.

## Planned implementation files (future only)

- `am-visual-builder/schema/entities/nursery/contact.php`
- `am-visual-builder/admin/pages/nursery/sections/ContactInfoEditor.jsx`
- `am-visual-builder/runtime/nursery/ContactInfoSection.php`
- `am-visual-builder/content/nurseries/hounslow/contact.json`

## Acceptance checklist

- [ ] Current Hounslow address, phone, email, labels, links, and 1/3-column layout reproduce.
- [ ] Validation and downstream warnings prevent cross-page inconsistency.
- [ ] Keyboard order, focus, wrap, missing fields, safe helper fallback, audit, and restore pass.

