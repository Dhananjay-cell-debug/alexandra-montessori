# Heston — booking modal

## Intent and feel

Give Heston families a dependable booking overlay and make the current temporary shared Calendly destination impossible to mistake for a final branch mapping.

## Current public section → exact WP canvas parity

- Functional overlay opens from Heston hero and is mounted after sections; current heading is `Book a visit to Heston` with backdrop, close control, and iframe.
- Current temporary URL still uses active event `dhananjaychitmila/book-a-visit-hounslow`, but query attribution is `utm_campaign=heston` and `utm_content=Heston`.
- Escape/backdrop close and body scroll locking are current behaviours.

## Editable elements and controls

- Heston event mapping, provider/destination, heading template, allowed UTM preview, modal size/tokens, failure contact, and connection/embed test.
- Display strong `Temporary shared event` status until client Heston event is activated; secrets remain protected settings.

## Layers and dragging

- Exact overlay tree: `Booking modal` → `Backdrop`, `Dialog` → `Header` → `H2`, `Close`; `Scheduling iframe`.
- Stack/focus locked; no page-flow dragging; approved resize handles only.

## Responsive behaviour

- Safe mobile viewport, reachable close, orientation/keyboard handling; desktop 90vh/max-3xl parity.

## Record/template binding and overrides

- Heston owns mapping and UTM identity; structure inherits template. Replacing temporary event changes only Heston mapping.

## Protected rules

- Allowlisted HTTPS embed, sanitized params, no raw scripts, preserve `utm_campaign=heston`, focus/scroll cleanup on every path.

## Accessibility

- Dialog semantics, Heston label, iframe title, initial focus/focus trap/Escape/return focus/visible close focus required.

## Empty and error states

- Invalid Heston embed shows local contact fallback; slow load announces progress and retry avoids duplicate booking.

## Storage and versioning

- Store non-secret mapping at `nurseries/{hestonUuid}/booking`; protected provider credentials outside page JSON.
- Version mapping/test status/migration note/copy/style without storing secrets.

## Planned implementation files (future only)

- `am-visual-builder/schema/entities/nursery/booking.php`
- `am-visual-builder/admin/pages/nursery/BookingModalEditor.jsx`
- `am-visual-builder/runtime/nursery/BookingModal.php`
- `am-visual-builder/content/nurseries/heston/booking.json`

## Acceptance checklist

- [ ] Current Heston heading, shared active event, and Heston UTM reproduce.
- [ ] Temporary status and eventual Heston-only replacement are clear.
- [ ] Focus/scroll/open/close, responsive, error fallback, connection test, versioning, and secret exclusion pass.

