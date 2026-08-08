# Blog article — Loading public section plan

## 1. Intent and feel
Make archive retrieval feel calm and intentional, with concise reassurance instead of a blank page or distracting animation.

## 2. Current React/public evidence and live-canvas parity
When no post is available and loadState is loading, BlogArticle.jsx early-returns one container section aria-live="polite" with a centred card: “Blogs & Resources”, h1 “Loading article” and explanatory paragraph. No Seo component is currently emitted in this early branch. Canvas must run this exact branch and must not invent a skeleton or header.

## 3. Exact editable elements and controls
Expose eyebrow, h1 and body copy; card/section background, border/radius/shadow, width/padding/alignment; typography, spacing and responsive overrides. Optional motion controls are not offered because current output has no spinner/animation. Page-level note reports that this branch currently has no React Seo output rather than pretending otherwise.

## 4. Layers and reorder rules
Section > loading card > eyebrow > h1 > paragraph. Semantic order is locked; this branch is mutually exclusive with error, not-found and normal article sections.

## 5. Responsive behavior
Preserve container padding and card p-8/p-12 with 3xl/4xl h1. Allow bounded device widths/padding/type; never force viewport height or clip long translated text.

## 6. Data ownership and binding
Loading copy/styles belong to the blog article template. Request key/status comes from fetchBlogArticle state and is not editable or persisted.

## 7. Protected functional logic
Protect condition !post && loadState === loading, early return, aria-live and absence of normal sections. Visual edits cannot hold the page in loading or initiate requests. Adding SEO here would be a separate React behavior task, not a visual-layer edit.

## 8. Accessibility
Keep aria-live polite, one h1, adequate contrast and no rapidly updating/animated content. If future progress visuals appear, they must be decorative or properly named without repetitive announcements.

## 9. Builder state previews
Loading fixture selects this real early return with short/long copy and three devices. It never waits on a network call in preview and is clearly labelled State simulation.

## 10. Storage, publishing, and versioning
Store blog-article-loading shell in revisioned template design; state selection remains editor-only. Sanitize copy/style bounds and publish alongside the other template branches atomically.

## 11. Planned implementation files (future only)
Add loading-region hooks/design reads to BlogArticle.jsx; register loading branch in future class-am-vb-blog-design.php; add early-return state preview to assets/editor.js/canvas.css; test branch exclusivity and aria-live parity.

## 12. Acceptance checklist
- [ ] Canvas loading view is the actual early-return section.
- [ ] No normal article or invented skeleton is visible.
- [ ] aria-live and h1 remain correct.
- [ ] Preview performs no request.
- [ ] Publish/restore includes this branch styling.
