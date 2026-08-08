# Event detail — Dynamic page header plan

## 1. Intent and feel
Give every event a clear, composed title while signaling to editors that they are styling a reusable bound template, not overwriting an individual event record.

## 2. Current React/public evidence and live-canvas parity
EventDetail.jsx resolves the route via eventSlug. A match sends ev.title and ignored crumb="Event" to PageHeader; a miss sends “Event not found”. PageHeader currently renders only the centred h1 section. Seo is dynamic for a match and fallback for a miss. Canvas must not invent a breadcrumb.

## 3. Exact editable elements and controls
Expose background, padding, container, alignment and responsive bound-title typography. Provide a preview Event selector, locked {event.title} badge, editable not-found title and SEO fallback/pattern/social-image settings. Canonical route pattern stays locked.

## 4. Layers and reorder rules
Section > container > bound Event title h1. Header stays first and the binding cannot be detached, duplicated or converted to static copy. No crumb layer exists in the plan because none is publicly rendered.

## 5. Responsive behavior
Retain PageHeader 4xl/5xl defaults with device overrides. Stress-test long event names and preserve natural height/centred wrap.

## 6. Data ownership and binding
Events owns title/slug/excerpt/image. Template design owns styles and not-found/SEO fallback strings. Selected preview Event is transient editor context.

## 7. Protected functional logic
Protect eventSlug lookup, one h1, matched/missing branch and canonical /events/{slug}. Removing an Event must automatically activate existing not-found behavior rather than preserve stale content.

## 8. Accessibility
Maintain one h1, AA contrast and readable wrap. Clearly label bound content in the editor without adding badges to public output.

## 9. Builder state previews
Preview matched Event, long title and Event not found. No loading/error branch is invented because the current collection lookup is synchronous.

## 10. Storage, publishing, and versioning
Store as event-detail-header in revisioned template design; omit event record/selected fixture. Validate required binding tokens and publish one shell to all event routes.

## 11. Planned implementation files (future only)
Add template hooks to EventDetail.jsx/PageHeader.jsx; register binding/schema in future class-am-vb-events-design.php; add Record/States controls to assets/editor.js and canvas CSS; test matched/missing parity.

## 12. Acceptance checklist
- [ ] Selected Event title is bound and read-only.
- [ ] Missing route shows exact fallback header.
- [ ] No breadcrumb is invented.
- [ ] Long mobile title is safe.
- [ ] SEO/canonical remain correct.
