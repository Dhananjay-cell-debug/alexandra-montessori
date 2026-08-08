# Blog article — Closing help CTA plan

## 1. Intent and feel
After parent guidance, offer a warm human conversation about nursery places or funded childcare, giving the article a useful service-oriented close.

## 2. Current React/public evidence and live-canvas parity
The normal BlogArticle branch calls CTA with title “Need help with a nursery place or funded childcare?” and supporting text. CTA.jsx renders a sand section and its current /nurseries “Choose a nursery” link. Loading, error and not-found early returns do not render this CTA. Canvas must mirror that branch placement exactly.

## 3. Exact editable elements and controls
Expose title, body, button label and typed internal destination with current seed /nurseries; section background/padding; heading/body widths/type/colour; button normal/hover/focus; alignment/reveal. Distinguish template-instance overrides from shared CTA defaults.

## 4. Layers and reorder rules
Section > Reveal container > h2 > paragraph > link. Keep CTA last in the normal article branch and absent from early states. Internal order is fixed.

## 5. Responsive behavior
Preserve centred 3xl/4xl heading, max-w-xl body and natural height. Allow bounded mobile padding/type/full-width action.

## 6. Data ownership and binding
CTA shell belongs to the blog-detail template and may inherit shared CTA defaults. It does not bind to the current Article or related records.

## 7. Protected functional logic
Keep React Router internal link and render only when post exists. Instance edits cannot leak to archive/events CTA instances unless shared defaults are intentionally edited.

## 8. Accessibility
Maintain h2 hierarchy, descriptive link, visible focus, 44px target, AA contrast and readable line length.

## 9. Builder state previews
Preview normal article with inherited/overridden CTA, hover/focus, long copy and mobile; verify loading/error/not-found previews omit it exactly.

## 10. Storage, publishing, and versioning
Store blog-article-closing-cta overrides/inheritance metadata in revisioned template snapshots. Validate link/copy and publish atomically with all normal/article-state branches.

## 11. Planned implementation files (future only)
Add article-template CTA instance key/design props in BlogArticle.jsx/CTA.jsx; define inheritance in future class-am-vb-blog-design.php; add shared/instance inspector to assets/editor.js/canvas.css; test branch absence and cross-page isolation.

## 12. Acceptance checklist
- [ ] CTA appears only in the normal Article branch.
- [ ] Initial canvas matches current /nurseries action.
- [ ] Instance edits stay isolated.
- [ ] Link/focus/mobile checks pass.
- [ ] Inheritance and revision restore are exact.
