# Event detail — Not-found section plan

## 1. Intent and feel
Offer a soft recovery when an event URL is stale: a brief explanation and one clear route back to the current archive.

## 2. Current React/public evidence and live-canvas parity
When eventSlug finds no record, EventDetail.jsx renders a white section with “We couldn't find that event. It may have already taken place.” and a Back to events primary link. It follows the Event not found PageHeader and replaces the live detail section.

## 3. Exact editable elements and controls
Expose body copy; Back label; validated internal /events destination; button normal/hover/focus presentation; section/background/padding/max width/alignment and responsive typography. SEO fallback lives in header/page settings.

## 4. Layers and reorder rules
Section > centred container > message > action wrapper > Back link. Order is fixed for comprehension. This branch cannot coexist publicly with event-detail-content.

## 5. Responsive behavior
Preserve max-w-xl message and centred action. Allow bounded device padding/type/button width; long copy must wrap and mobile target stays usable.

## 6. Data ownership and binding
Fallback copy/styles belong to the event detail template. The state is determined only by the Events collection/slug lookup; no list of missing Events is stored.

## 7. Protected functional logic
Protect !ev condition, mutual exclusivity and safe /events navigation. A design cannot force fallback for a valid record or expose deleted record fields. Current code has no retry/loading state.

## 8. Accessibility
Provide plain readable text, visible focus, descriptive action, AA contrast and 44px target. Heading context comes from the preceding PageHeader h1.

## 9. Builder state previews
Use a Missing Event fixture to run the real branch, plus long-copy, hover/focus and mobile states. No fabricated error section is added.

## 10. Storage, publishing, and versioning
Store event-detail-not-found under the shared template revisions with sanitized text and typed internal link. Do not serialize route slug or deleted Event data.

## 11. Planned implementation files (future only)
Add fallback hooks to EventDetail.jsx; define conditional schema/link validation in future class-am-vb-events-design.php; add missing-record preview in assets/editor.js and parity CSS/tests.

## 12. Acceptance checklist
- [ ] Missing slug shows this exact branch; valid slug never does.
- [ ] Back action remains valid and accessible.
- [ ] Mobile and canvas/live presentation match.
- [ ] No deleted Event data is stored.
- [ ] Restore/publish is reversible.
