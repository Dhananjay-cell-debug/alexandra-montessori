# Blog archive — Featured article section plan

## 1. Intent and feel
Give one editorially featured resource generous visual priority while keeping the card calm, credible and clearly connected to the article archive.

## 2. Current React/public evidence and live-canvas parity
When featured is truthy, Blogs.jsx renders a container section with eyebrow “Featured article” and a linked card. The card contains bound image, category pill, calendar/date, title, excerpt and Read more/ArrowUpRight. featured comes from archive data, falling back to initialArchive.featured even during a request. Canvas must run this actual conditional section/card.

## 3. Exact editable elements and controls
Expose section padding; “Featured article” label; linked-card background/border/radius/shadow/hover; desktop grid ratio/gap/padding; image ratio/radius/focal presentation; category/date pill/icon styles; bound title/excerpt typography and widths; Read more label/icon and hover/focus states; date-format preset. Provide read-only selected featured Article preview and Manage Articles link.

## 4. Layers and reorder rules
Section > label > linked featured card > image and content column > category/date metadata > title > excerpt > action. Desktop image/content columns may swap as whole regions; content semantic order remains metadata/title/excerpt/action. The whole card remains one link; nested links/buttons are prohibited.

## 5. Responsive behavior
Preserve stacked mobile and two-column lg layout with 16:10 image. Allow bounded column ratio/gap/padding/type per device; long titles/excerpts/categories wrap without causing nested overflow.

## 6. Data ownership and binding
Articles/archive response owns featured selection, slug, image, category, date, title and excerpt. Builder owns the section label, action label, date display and presentation shell. It does not choose or clone the featured record; editorial selection stays in Articles/archive management.

## 7. Protected functional logic
Protect featured truthiness conditional, fallback behavior, /blogs/{slug} route, whole-card link semantics, bound alt/title and safe date formatting. Visual editing cannot make a non-existent featured card appear on public output or change which Article is featured.

## 8. Accessibility
Ensure one descriptive card link, meaningful image alt, visible focus around the whole card, logical h2 after the page h1, adequate pill/text contrast and decorative icon treatment. Avoid repeated action text being the only accessible name; article title is within the same link.

## 9. Builder state previews
Preview featured present, no featured (section absent), missing image/category/excerpt fallback fixture, long title, hover/focus and stacked/two-column devices. No state selection changes Articles.

## 10. Storage, publishing, and versioning
Store blogs-featured-shell in /blogs revisions with labels/order/styles and allow-listed date format; exclude featured record. Published schema is compatible with nullable featured data and removes the section naturally when absent.

## 11. Planned implementation files (future only)
Add stable region/slot bindings to the featured JSX in Blogs.jsx; register shell schema in future class-am-vb-blog-design.php; add Record preview/Layers/States UI to assets/editor.js and canvas CSS; test conditional/fallback/link parity.

## 12. Acceptance checklist
- [ ] Featured present/absent canvas state matches archive response behavior.
- [ ] Article fields remain bound and read-only.
- [ ] Whole-card navigation/focus remains correct.
- [ ] Mobile/desktop layouts survive long/missing optional data.
- [ ] Revisions contain no Article copy.
