# Hammersmith Contact Details Card Plan

## Implementation-grade parity contract

### Intent and feel

The details card should feel like a compact, trustworthy branch directory: every fact is scannable, actionable where appropriate, and visibly sourced from Hammersmith.

### Exact current React/public evidence

The first `card p-7` rail card in ContactLocation.jsx contains four rows in order: MapPin / Visit us / `Dalling Road, London W6 0EU`; Phone / Call us / `0204 618 3477`; Mail / Email us / `hammersmith@alexandramontessori.co.uk`; Clock / Opening hours / `Mon-Fri, 8am-6pm`. Icons sit in 44px sage rounded badges; phone uses `telHref` and email uses Gmail compose in a safe new tab.

### Current public section -> exact WP canvas parity

| Current public truth | Required WordPress canvas result |
| --- | --- |
| Rows/order | Render Visit, Call, Email, Opening hours in the exact current order with the same icons, labels and bound values. |
| Actions | Generate `tel:02046183477` and the current Gmail-compose destination from source data; address/hours stay text. |
| Appearance | Seed `card p-7`, `space-y-4`, flex row with `gap-3`, 44px rounded-xl sage icon badge and current label/value typography. |

### Editable elements and controls

Row labels, approved icons, order, visibility, optional directions action, card surface/border/radius/shadow/padding/gap and typography. Factual values expose Edit Nursery source; an explicit local override requires elevated permission and badge.

### Layers, reorder, and dragging

Card > ordered rows > icon badge + label/value. Rows drag/reorder with keyboard parity and DOM order; facts remain paired with labels/actions. Icons cannot float away from their row, and factual text cannot be placed behind decorative layers.

### Responsive behavior

Maintain row flow and wrapping at 320px/200% zoom; email uses break-all where needed. Optional desktop compact variants must preserve 44px targets and mobile natural height.

### Data ownership and bindings

Address, phone, email and hours bind to Hammersmith Nursery fields. Static row labels/icons and card style are template-owned. Link construction is shared helper/renderer behavior.

### Protected behavior

Stable Nursery binding, validated phone/email link generation, new-tab rel, sanitization, and at least one reachable contact method readiness warning. Designers cannot paste recipients into style controls.

### Accessibility

Rows retain text labels, so icons are decorative. Links have visible focus and understandable names; long values wrap; colour alone does not distinguish actions; heading/landmark context remains valid.

### Relevant state previews

Preview exact four rows, missing phone, missing email, long address, long email, reordered rows, one contact method, invalid source value and keyboard focus.

### Storage and versioning

Version template row schema/style under `contact_location.details_card` and optional `hammersmith` order/visibility overrides. Store field references, not copied Hammersmith facts; source edits and template edits have separate audit/rollback.

### Planned implementation files (future only)

These paths describe later implementation ownership; they are **not created by this planning phase**.

- `includes/definitions/templates/contact-location.php` -- details-card row/binding schema.
- `includes/renderers/sections/contact-details-card.php` -- safe icon rows and actions.
- `assets/editor/templates/contact-location/hammersmith-details-card.js` -- Hammersmith source-aware row inspector.
- `tests/parity/contact-location/hammersmith-details-card.spec.js` -- exact values, URLs, order and wrapping.

### Acceptance checklist

- [ ] All four current Hammersmith values and labels match the published Nursery record.
- [ ] Phone/email destinations are valid, safe, keyboard accessible and unchanged by visual reordering.
- [ ] Missing or long source data degrades honestly without undefined or broken layout.


## Exact current section

The card binds Dalling Road, London W6 0EU; Hammersmith phone/email; and Mon-Fri 8am-6pm into Visit, Call, Email, and Opening hours rows.

## Editing model

Row labels, icons, order, visibility, card surface, typography, spacing, radius, border, and shadow are page-style controls. Facts remain Hammersmith Nursery bindings. Valid tel/email generation and focus behavior are protected.

## Acceptance

No Hounslow/Heston values leak into this page. Long email and Dalling Road address wrap cleanly. One source edit updates every bound occurrence site-wide.
