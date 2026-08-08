# Event detail — Bound content and actions section plan

## 1. Intent and feel
Present practical event information in a friendly, highly scannable order: image, when/where, what to expect, booking notes and a decisive next step.

## 2. Current React/public evidence and live-canvas parity
For a matched Event, EventDetail.jsx renders one white section. It conditionally shows image, a metadata row with formatted date/time/location, excerpt, sanitized description HTML, booking-information card, then Book a place and Back to events. timeLabel prefers startTime plus endTime, then time, then startTime. Book a place links to /contact?event={encoded title}. Canvas must execute this exact section and option conditions.

## 3. Exact editable elements and controls
Expose section/container/padding/max width; image ratio/radius/focal display; metadata icon mapping/size/colour/gap and date-format preset; excerpt/rich-text typography; booking-card label “Booking information”, colours/border/radius/padding; Book a place and Back labels/button/link styles. Bound Event fields are selectable read-only slots with Manage Event link; alt can bind title or an Event-provided alt when supported.

## 4. Layers and reorder rules
Section > optional image > metadata group > optional excerpt > optional description > optional booking card > action row. Blocks may reorder as whole units only if action row remains last and DOM order follows visual order. Inside metadata, date/time/location slots may reorder; conditional fields collapse without blank layers.

## 5. Responsive behavior
Preserve max-w-4xl flow, 16:9 image and wrapping metadata/action rows. Allow bounded device spacing/type/image treatment; long locations/time ranges and rich content must wrap, tables/media in sanitized HTML must remain within viewport, and mobile actions may stack.

## 6. Data ownership and binding
Events owns image, title, date, time/startTime/endTime, location, excerpt, sanitized description, bookingInfo and slug. Builder owns labels, date display preset and shell styles. The Book query is derived from bound title at runtime; it is not editable record content.

## 7. Protected functional logic
Protect optional rendering, date/time fallback order, server-side HTML sanitization, event title URL encoding, /contact booking route and /events return route. Do not silently hide booking for past events because current JSX does not; any future behavior change requires explicit public logic work, not a visual state.

## 8. Accessibility
Image needs meaningful alt, icons are decorative beside text, metadata remains readable text, rich content hierarchy is checked beneath h1, booking card h2 is logical, and actions have visible focus/44px targets. External HTML cannot introduce unsafe heading jumps or inaccessible embeds.

## 9. Builder state previews
Preview complete Event, upcoming/past dates, missing image/time/location/excerpt/description/bookingInfo, separate/combined time fields, long rich content, long location and mobile action wrapping. Fixtures do not update Events or contact forms.

## 10. Storage, publishing, and versioning
Store event-detail-content shell/order/styles in revisioned template document; exclude Events. Use allow-listed bound slots/date formats and schema compatibility checks. Published snapshot updates all event detail URLs atomically and retains a rollback path.

## 11. Planned implementation files (future only)
Instrument EventDetail.jsx with region/slot hooks and shell-token reads; add event binding/sanitizer to future class-am-vb-events-design.php; add Layers, Record and States inspector in assets/editor.js; add canvas rich-content parity CSS; test optional blocks, times and booking query.

## 12. Acceptance checklist
- [ ] One shell renders every Event without copied content.
- [ ] Optional blocks and time fallback remain exact.
- [ ] Book action carries the correctly encoded Event title.
- [ ] Rich content/actions are accessible and responsive.
- [ ] Canvas and public detail match across fixtures/devices.
