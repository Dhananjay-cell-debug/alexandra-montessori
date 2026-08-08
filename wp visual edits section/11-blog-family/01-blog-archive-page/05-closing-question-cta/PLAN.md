# Blog archive — Closing question CTA plan

## 1. Intent and feel
End the archive with a human offer of help for questions an article did not answer, while retaining the site's calm shared CTA rhythm.

## 2. Current React/public evidence and live-canvas parity
Blogs.jsx calls CTA with title “Have a question we haven't answered?” and supporting text. CTA.jsx renders a sand section plus its current fixed /nurseries “Choose a nursery” link. Canvas must edit this exact /blogs CTA instance and preserve its current public output at initial seed.

## 3. Exact editable elements and controls
Expose title/body/button label/internal destination; current seed keeps /nurseries and Choose a nursery; heading/body/button presentation, background, padding, widths, alignment, hover/focus and reveal. Show shared CTA defaults separately from /blogs instance overrides.

## 4. Layers and reorder rules
Section > Reveal container > h2 > paragraph > link. CTA stays last and internal order is fixed. It cannot be nested into archive results or featured card.

## 5. Responsive behavior
Preserve centred 3xl/4xl heading and max-w-xl body. Allow bounded mobile padding/type/button width with natural height.

## 6. Data ownership and binding
This instance's copy/styles/link belong to /blogs shell and may inherit shared CTA defaults. It does not bind to Articles or archive query state.

## 7. Protected functional logic
Keep one React Router link and safe internal destinations. Instance edits must not leak to /events or other CTA users unless shared defaults are intentionally selected.

## 8. Accessibility
Maintain h2 hierarchy, descriptive link label, visible focus, 44px target, AA contrast and comfortable line length.

## 9. Builder state previews
Preview inherited, instance override, hover/focus, long copy and mobile after ready/error/empty archive fixtures. CTA itself has no loading/error state.

## 10. Storage, publishing, and versioning
Store blogs-closing-cta overrides with explicit inheritance metadata in /blogs revisions. Validate links/text, publish atomically and restore inheritance accurately.

## 11. Planned implementation files (future only)
Add /blogs instance key/design props in Blogs.jsx and CTA.jsx; define inheritance schema in future class-am-vb-blog-design.php; add shared/instance controls to assets/editor.js and canvas CSS; test isolation and link parity.

## 12. Acceptance checklist
- [ ] Initial canvas matches current CTA including /nurseries action.
- [ ] Instance changes do not affect other pages.
- [ ] Link remains valid and accessible.
- [ ] Mobile copy remains stable.
- [ ] Inheritance/revision restore is reversible.
