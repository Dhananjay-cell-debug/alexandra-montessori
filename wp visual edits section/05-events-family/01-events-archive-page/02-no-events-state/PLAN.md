# Events archive — No-events public state plan

## 1. Intent and feel
Make a temporarily empty archive feel cared for rather than abandoned: a gentle calendar mark, honest message and clear way to register interest.

## 2. Current React/public evidence and live-canvas parity
When splitEvents returns no upcoming and no past records, Events.jsx renders one white section with a circular CalendarHeart icon, h2 “We currently have no events coming up”, explanatory paragraph and Link to /contact labelled “Register your interest”. This branch replaces the populated archive section but is followed by the same closing CTA.

## 3. Exact editable elements and controls
Expose icon choice/size/stroke/foreground/circle background; h2 and paragraph; internal action label/destination default /contact; primary-button states; section/background/padding/max width; vertical gaps, alignment and responsive typography. Provide reset and safe internal-link picker.

## 4. Layers and reorder rules
Section > container > centred state group > icon circle > h2 > body > action. Elements remain in this semantic order; icon may be hidden but not replaced by unlabelled media. This conditional section cannot be dragged to coexist with populated collections.

## 5. Responsive behavior
Preserve the 16x16 icon circle, 3xl/4xl heading and max-w-xl centred column by default. Allow bounded device padding/type/button width; mobile copy must not be clipped or produce horizontal scroll.

## 6. Data ownership and binding
State copy and presentation are /events shell data. The empty condition is calculated from the Events collection after date splitting; visual design stores no event records and cannot manually declare the archive empty.

## 7. Protected functional logic
Protect hasAny === false as the sole public trigger, safe contact navigation and mutual exclusivity with populated collections. Current code has no network loading/error state; do not add one to public output through the visual shell. Note that past records make hasAny true even when upcoming is empty—preserve current behavior.

## 8. Accessibility
Treat CalendarHeart as decorative, retain h2 hierarchy after page h1, provide descriptive link text, visible focus, sufficient contrast and 44px target size. Avoid anxiety-inducing or misleading copy validation guidance.

## 9. Builder state previews
A No event records fixture selects the real branch. Also preview long text, icon hidden, button hover/focus and mobile. The state toggle is editor-only and cannot modify Events.

## 10. Storage, publishing, and versioning
Store under events-empty-state in /events revisions with typed icon/internal link and sanitized text. Publish readiness validates the destination and preserves a published shell independently of Event records.

## 11. Planned implementation files (future only)
Add exact empty-region hooks/design-token reads to Events.jsx; define conditional shell schema in future class-am-vb-events-design.php; add empty-record fixture and element inspector to assets/editor.js; add canvas parity/focus CSS and branch tests.

## 12. Acceptance checklist
- [ ] Zero Events selects the same empty section in canvas and public route.
- [ ] Any upcoming or past Event selects the populated branch instead.
- [ ] Contact action is valid and accessible.
- [ ] Mobile/long copy match live output.
- [ ] Design revisions contain no Event records.
