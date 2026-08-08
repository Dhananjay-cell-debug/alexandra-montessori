# Events archive — Closing visit CTA plan

## 1. Intent and feel
Close the archive with a warm invitation to visit, offering a useful next step regardless of whether event listings are populated.

## 2. Current React/public evidence and live-canvas parity
Events.jsx renders CTA after both conditional archive branches with title “Want to visit us in person?” and supporting text. CTA.jsx outputs one sand section and a fixed Link to /nurseries labelled “Choose a nursery”. The canvas must edit this exact shared component instance on /events.

## 3. Exact editable elements and controls
Expose title, body and instance-level button label; approved internal destination with current /nurseries default; section background/padding; heading/body width/type/colour; button normal/hover/focus styles; alignment and reveal. Make inherited shared-CTA defaults versus /events overrides explicit.

## 4. Layers and reorder rules
Section > Reveal container > h2 > paragraph > link. Keep the CTA last on /events and retain internal order. It cannot be absorbed into either conditional archive branch.

## 5. Responsive behavior
Preserve 3xl/4xl heading, max-w-xl body and centred flow. Allow mobile padding/type/button width overrides without fixed height or clipped text.

## 6. Data ownership and binding
Text and styling are /events CTA instance settings, optionally inheriting from the shared CTA component design. Destination resolves through the internal page registry; it is unrelated to Event records.

## 7. Protected functional logic
Keep Link semantics and safe internal navigation. The CTA renders after empty or populated archive exactly once. Editing this instance must not unintentionally change CTAs on Blogs or Testimonials unless the user explicitly edits shared defaults.

## 8. Accessibility
Maintain h2 hierarchy, descriptive button text, visible focus, 44px target and AA contrast. Show a warning if copy creates duplicate ambiguous CTAs nearby.

## 9. Builder state previews
Preview inherited, locally overridden, hover/focus, long copy and mobile states after both real archive fixtures. There is no loading/error CTA state.

## 10. Storage, publishing, and versioning
Store events-closing-cta as instance overrides with explicit inheritance metadata in /events revisions. Typed internal links and sanitized text publish atomically; restoring removes or reinstates overrides accurately.

## 11. Planned implementation files (future only)
Add an events instance key/design props to Events.jsx and CTA.jsx; define inheritance/sanitization in future class-am-vb-events-design.php; add Shared vs this page controls in assets/editor.js; add canvas parity CSS and isolation tests.

## 12. Acceptance checklist
- [ ] CTA appears once after either archive branch in canvas/live page.
- [ ] Instance edits do not leak to unrelated pages.
- [ ] Destination remains valid and keyboard accessible.
- [ ] Mobile long copy remains stable.
- [ ] Inheritance and revisions are understandable and reversible.
