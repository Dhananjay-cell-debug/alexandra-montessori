# Hammersmith — booking modal

## Intent and feel

Keep Hammersmith booking accessible while clearly representing the temporary shared Hounslow Calendly event and preserving Hammersmith attribution.

## Current public section → exact WP canvas parity

- Functional overlay after all sections opens from hero; heading `Book a visit to Hammersmith`, close control, backdrop, 90vh/max-3xl iframe.
- Temporary URL uses `dhananjaychitmila/book-a-visit-hounslow`, but `utm_campaign=hammersmith` and `utm_content=Hammersmith`.
- Escape/backdrop close and body scroll lock are current behaviours.

## Editable elements and controls

- Hammersmith event/provider mapping, heading, allowed UTM preview, size/tokens, failure contact, embed test; strong temporary-status banner; secrets outside canvas.

## Layers and dragging

- Exact overlay tree: `Booking modal` → `Backdrop`, `Dialog` → `Header` → `H2`, `Close`; `Scheduling iframe`.
- Stack/focus locked; no page dragging; approved resize bounds.

## Responsive behaviour

- Safe mobile viewport/close/keyboard/orientation; desktop preserves current dimensions.

## Record/template binding and overrides

- Hammersmith owns mapping/UTM; modal structure inherited. Future branch event changes Hammersmith only.

## Protected rules

- Allowlisted HTTPS, sanitized params, preserve Hammersmith campaign identity, no raw scripts, focus/scroll cleanup.

## Accessibility

- Dialog semantics/label, iframe title, initial focus/trap/Escape/return focus/visible close focus.

## Empty and error states

- Invalid embed shows Hammersmith contact fallback; slow-load progress/retry avoids duplicate booking.

## Storage and versioning

- Non-secret mapping at `nurseries/{hammersmithUuid}/booking`; credentials protected. Version mapping/test/migration/copy/style, exclude secrets.

## Planned implementation files (future only)

- `am-visual-builder/schema/entities/nursery/booking.php`
- `am-visual-builder/admin/pages/nursery/BookingModalEditor.jsx`
- `am-visual-builder/runtime/nursery/BookingModal.php`
- `am-visual-builder/content/nurseries/hammersmith/booking.json`

## Acceptance checklist

- [ ] Current heading/shared event/Hammersmith UTM reproduce.
- [ ] Temporary status and branch-only migration are clear.
- [ ] Focus/scroll/open/close, responsive, failure, test, versions, and secret exclusion pass.

