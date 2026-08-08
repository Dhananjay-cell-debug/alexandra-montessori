# Blog article — Back link, article introduction and image section plan

## 1. Intent and feel
Give each article an editorial, trustworthy opening: easy archive return, clear category/date, strong title, concise introduction and warm lead image.

## 2. Current React/public evidence and live-canvas parity
For a bound post, BlogArticle.jsx emits dynamic Seo then one section with Back to blogs link and a responsive two-column grid. Text card contains bound category, CalendarDays/date, h1 title and computed articleIntro; image column binds post.image/title and is visually first on mobile, second on desktop. getArticleIntro prefers a complete excerpt, otherwise derives up to two sanitized content paragraphs. Canvas must execute this exact logic/section.

## 3. Exact editable elements and controls
Expose Back label/style; section padding; grid ratio/gap/alignment; text-card background/border/radius/shadow/padding; category pill/date/icon styles and date format; bound title/intro typography/width; image ratio/radius/shadow/object focus; desktop/mobile column presentation; SEO title suffix/pattern and image fallback. Article fields are locked with Manage Article link.

## 4. Layers and reorder rules
Section > Back link > hero grid > text card and image > metadata > h1 > intro. Preserve mobile image-first/current order and desktop text-first through responsive CSS; optional controlled swap must update intended device order without making keyboard/DOM sequence confusing. Bound metadata/title/intro remain grouped and h1 cannot duplicate.

## 5. Responsive behavior
Mirror current stacked mobile image-first and lg 1.05fr/0.95fr text-first visual arrangement. Allow bounded gaps/padding/type/image ratio by device. Long titles/categories/intro wrap; image remains aspect-safe and no fixed height clips content.

## 6. Data ownership and binding
Articles owns slug, title, category, date, excerpt, content and image. getArticleIntro derives display text at runtime. Template owns Back label, date preset and shell styles. Selected preview Article and computed intro are not serialized.

## 7. Protected functional logic
Protect /blogs return route, full intro derivation/HTML stripping/sentence-boundary logic, dynamic Seo/canonical/image, whole data bindings and priority image behavior. Visual editing cannot overwrite articleIntro or convert it into static template copy.

## 8. Accessibility
Keep one h1, meaningful image alt, visible Back focus, logical metadata reading order and sufficient contrast. Verify CSS visual order does not create confusing screen-reader/keyboard order; icons are decorative.

## 9. Builder state previews
Preview complete excerpt, truncated/incomplete excerpt using derived content, no excerpt, long title/category, missing image fallback, focus/hover and mobile/desktop column order. Fixtures use real Article shapes without saving them.

## 10. Storage, publishing, and versioning
Store blog-article-hero shell/order/styles/SEO patterns in revisioned template design; exclude Article/computed intro. Allow-list bound slots and date format; publish once for all Article routes with safe defaults for missing fields.

## 11. Planned implementation files (future only)
Instrument BlogArticle.jsx hero slots and Img with stable hooks/design tokens; add article binding/pattern schema to future class-am-vb-blog-design.php; build Record/Layers/States UI in assets/editor.js and parity CSS; test intro derivation, ordering and SEO.

## 12. Acceptance checklist
- [ ] Selected Article fields remain bound/read-only.
- [ ] Intro derivation remains byte-for-behavior equivalent.
- [ ] One h1, back link, SEO and image alt remain correct.
- [ ] Mobile/desktop order is visually and semantically safe.
- [ ] Template revisions contain no Article copy.
