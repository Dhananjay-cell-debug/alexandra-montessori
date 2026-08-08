# Blog article — Rich content section plan

## 1. Intent and feel
Make long-form parent guidance beautifully readable while preserving the Article editor as the single source for actual body content.

## 2. Current React/public evidence and live-canvas parity
The normal BlogArticle branch renders one container section with Reveal, an article card and a max-w-4xl blog-richtext div populated via dangerouslySetInnerHTML from post.content. The canvas must show the selected Article's real sanitized body through this same component tree.

## 3. Exact editable elements and controls
Expose section spacing; card background/border/radius/shadow/padding; rich-content max width; paragraph, heading h2–h6, list, link, blockquote, image/caption, table and code presentation; rhythm/line height; link hover/focus; device-specific type/spacing. Provide Manage article content link; do not make body HTML contenteditable in the visual template.

## 4. Layers and reorder rules
Section > Reveal > article card > bound rich-content slot. Article body nodes are an inspection outline, not draggable template layers; their order belongs to the Article record. The bound slot may not move outside this section or be duplicated.

## 5. Responsive behavior
Preserve p-7/p-10/p-12 and max-w-4xl defaults. Allow bounded content type/spacing; images, embeds, tables, code and long URLs must fit the mobile viewport with deliberate overflow behavior.

## 6. Data ownership and binding
Articles owns post.content and embedded media references. Template owns blog-richtext presentation only. The design document stores no HTML body, headings or media from the selected Article.

## 7. Protected functional logic
Protect sanitized HTML binding and output order. WordPress/API sanitization is authoritative; the visual builder cannot inject HTML/scripts or rewrite Article content. If unsupported markup appears, style fallback must remain readable rather than delete content.

## 8. Accessibility
Audit heading hierarchy beneath article h1, link names/focus, image alt/captions, table headers, list semantics, blockquote semantics and contrast. Flag record-level issues with a Manage Article link; template publishing cannot silently alter the record.

## 9. Builder state previews
Preview rich fixture containing all supported elements, very short article, long article, images, table overflow, long URL, nested lists and malformed-but-sanitized fallback across devices. Fixture content is transient.

## 10. Storage, publishing, and versioning
Store blog-article-richtext style tokens under template revisions; exclude post.content. Version supported-element style schema so new markup inherits safe defaults. Published CSS changes apply atomically across all articles and are rollbackable.

## 11. Planned implementation files (future only)
Add rich-content region hook/style token consumption to BlogArticle.jsx and blog-richtext CSS source; define bounded richtext schema in future class-am-vb-blog-design.php; add element-style tabs/audit links to assets/editor.js and canvas.css; create typography/overflow/accessibility fixtures/tests.

## 12. Acceptance checklist
- [ ] Visual editor never edits or stores body HTML.
- [ ] Selected Article content renders identically in canvas/public page.
- [ ] All supported semantic elements are styled and responsive.
- [ ] Unsafe HTML cannot enter through design controls.
- [ ] One style revision updates/rolls back every article consistently.
