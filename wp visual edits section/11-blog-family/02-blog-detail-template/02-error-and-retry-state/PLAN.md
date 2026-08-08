# Blog article — Request error and retry section plan

## 1. Intent and feel
Acknowledge temporary archive failure without blame and give the reader one immediate, confident recovery action.

## 2. Current React/public evidence and live-canvas parity
When no post exists and loadState is error, BlogArticle.jsx early-returns one container section with a card: eyebrow, h1 “The article could not be loaded”, explanatory text and Try again button. The button increments reloadToken. No Seo component is currently emitted in this branch. Canvas must render the same branch/button.

## 3. Exact editable elements and controls
Expose eyebrow, h1, body and Try again label; card/section spacing, width, background, border/radius/shadow; button normal/hover/focus/disabled styles and typography. Show the current no-SEO behavior as evidence; do not create a visual-only meta layer.

## 4. Layers and reorder rules
Section > error card > eyebrow > h1 > paragraph > retry button. Order and branch exclusivity are locked. Retry cannot be dragged outside the error card or converted to a link.

## 5. Responsive behavior
Preserve max-w-3xl and p-8/p-12 defaults; allow bounded device padding/type/button width. Long error copy wraps and button stays at least 44px tall.

## 6. Data ownership and binding
Error copy/styles belong to the article template. Error status/reloadToken belong to live request state and never enter design storage.

## 7. Protected functional logic
Protect !post && error condition, early return, retry's setReloadToken increment, fetch effect and AbortController behavior. Preview intercepts retry into a state transition; published retry invokes the real request. A style cannot suppress retry functionality.

## 8. Accessibility
Keep one h1, button semantics, visible focus, AA contrast and concise text. In planned hardening, error container should be announced without repeated assertive alerts; retry focus remains stable while the loading branch replaces it.

## 9. Builder state previews
Preview error idle, retry focus/hover, retry-to-loading transition, long copy and devices. The editor never calls the live endpoint unless explicit Live data mode is chosen.

## 10. Storage, publishing, and versioning
Store blog-article-error shell in the revisioned template; state/retry counts are excluded. Audit changes to recovery copy and publish all article-state branches atomically.

## 11. Planned implementation files (future only)
Add error-region/button hooks to BlogArticle.jsx; define branch schema in future class-am-vb-blog-design.php; add error/retry transition preview to assets/editor.js/canvas.css; test retry wiring and exclusivity.

## 12. Acceptance checklist
- [ ] Error canvas is the actual early-return branch.
- [ ] Retry remains a working button wired to reloadToken.
- [ ] Preview avoids accidental network requests.
- [ ] Focus/contrast/mobile states pass.
- [ ] Revisions restore all error copy/styles.
