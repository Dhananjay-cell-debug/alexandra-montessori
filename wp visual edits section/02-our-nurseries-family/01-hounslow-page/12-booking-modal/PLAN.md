# Hounslow — booking modal

## Intent and feel

Keep booking one confident click from the hero, while making the Hounslow scheduling destination diagnosable and safely configurable.

## Current public section → exact WP canvas parity

- This functional overlay is mounted after all public sections and opens from the Hounslow hero button; it is not a replacement page section.
- Current modal has ink-60 backdrop, centred 90vh/max-3xl white panel, heading `Book a visit to Hounslow`, close button, and lazy Calendly iframe.
- Current temporary routing uses `dhananjaychitmila/book-a-visit-hounslow` and UTM campaign `hounslow`; backdrop click and Escape close it, and body scroll is locked.

## Editable elements and controls

- Modal heading template, scheduling provider/destination selector, Hounslow event mapping, allowed query/UTM preview, height/width tokens, backdrop token, and optional failure contact action.
- A connection test validates embeddability and branch attribution before publish; secret/API credentials are never editable in canvas fields.

## Layers and dragging

- Exact overlay tree: `Booking modal` → `Backdrop`, `Dialog` → `Header` → `H2`, `Close`; `Scheduling iframe`.
- Stacking and focus order are locked; no layer can be dragged into page flow. Dialog size can use handles within approved bounds.

## Responsive behaviour

- Mobile uses safe viewport height/padding with reachable close control; desktop preserves 90vh/max-3xl.
- Handle keyboard viewport and orientation changes without losing booking state or trapping content offscreen.

## Record/template binding and overrides

- Hounslow owns event mapping/UTM identity; modal structure inherits shared template.
- Current active Hounslow event is an explicit temporary published value; future client mapping can replace it without altering Heston/Hammersmith records.

## Protected rules

- Allowlisted HTTPS scheduling domains only, sanitized parameters, no raw embed scripts, and immutable `utm_campaign=hounslow` attribution unless an authorised migration changes it.
- Modal must restore body scroll and focus on every close/unmount path.

## Accessibility

- `role=dialog`, `aria-modal`, Hounslow accessible label, labelled iframe, initial focus, focus trap, Escape close, return focus, and visible close focus are required.
- Backdrop click is supplemental, never the only close method.

## Empty and error states

- Invalid/unavailable embed shows a Hounslow-specific contact fallback without closing or displaying a blank frame.
- Slow load has a labelled progress state; timeout/retry does not create duplicate bookings.

## Storage and versioning

- Store non-secret mapping at `nurseries/{hounslowUuid}/booking`; provider credentials stay in protected site settings.
- Version mapping, tested status/time, modal copy/style, and migration notes; restore cannot expose retired credentials.

## Planned implementation files (future only)

- `am-visual-builder/schema/entities/nursery/booking.php`
- `am-visual-builder/admin/pages/nursery/BookingModalEditor.jsx`
- `am-visual-builder/runtime/nursery/BookingModal.php`
- `am-visual-builder/content/nurseries/hounslow/booking.json`

## Acceptance checklist

- [ ] Current Hounslow dialog, heading, iframe URL, and UTM attribution reproduce.
- [ ] Open/close, scroll lock, focus trap/return, Escape, backdrop, responsive viewport, load/error fallback, and connection test pass.
- [ ] Secrets never enter page JSON or revision history.

