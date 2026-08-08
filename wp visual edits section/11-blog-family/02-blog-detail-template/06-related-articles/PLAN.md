# Blog article — Related articles section plan

## 1. Intent and feel
Offer a gentle continuation after the article, making related reading easy to scan without competing with the main body or creating an editorial dead end.

## 2. Current React/public evidence and live-canvas parity
The normal branch always renders a section with “More to read”, h2 “More parent articles”, desktop View all link, and a three-column grid mapping result.related. Each linked card binds image, date, title, excerpt and Read article/ArrowUpRight. If related is empty, the heading and empty grid still render. Canvas must use this same section and data response.

## 3. Exact editable elements and controls
Expose eyebrow, h2, View all and Read article labels; internal /blogs destination; header layout; section spacing; grid columns/gap; related-card background/border/radius/shadow/hover; image ratio/focal presentation; date format/type; title/excerpt/action/icon styles. Show selected related fixture read-only and Manage Articles link; related selection itself stays editorial/API-owned.

## 4. Layers and reorder rules
Section > header row > eyebrow/h2 and View all > related grid > repeated whole-card link > image > date > h3 > excerpt > action. View all remains in header; action stays last in each link. Related record order is not draggable in template Layers.

## 5. Responsive behavior
Preserve hidden View all on xs, visible at sm, and one-column to three-column md grid. Allow bounded columns/gap/padding/type/image ratio. Empty grid leaves heading as current; long cards grow naturally without clipped excerpts.

## 6. Data ownership and binding
Article endpoint owns related array, slug, image, date, title and excerpt. Template owns headings/actions/date format and shell styles. No related record or selection is stored in design JSON.

## 7. Protected functional logic
Protect related response/order, exclusion of current Article as supplied by source, /blogs/{slug} links, /blogs View all, whole-card semantics and map keys. Do not invent substitute related records when array is empty; preview fixture may demonstrate but published output follows response.

## 8. Accessibility
Keep h2/h3 hierarchy, one descriptive link per card, meaningful image alt, visible focus, decorative arrow and adequate contrast. Hidden mobile View all is removed via display behavior; related cards remain reachable in source order.

## 9. Builder state previews
Preview three related, one related, empty related, missing optional image/excerpt, long title, card hover/focus and devices. Editor fixture choice does not modify relationships.

## 10. Storage, publishing, and versioning
Store blog-article-related-shell labels/styles/order in revisioned template document; exclude related array. Schema validates typed archive link and compatible card slots; published changes apply to all articles.

## 11. Planned implementation files (future only)
Instrument related section/card slots in BlogArticle.jsx; add related-shell binding schema to future class-am-vb-blog-design.php; build States/Record/Layers UI in assets/editor.js and canvas CSS; test empty/one/three, links and accessibility.

## 12. Acceptance checklist
- [ ] Related data remains API/Articles-owned and read-only.
- [ ] Empty related state matches current heading-plus-empty-grid output.
- [ ] Card/View all routes and focus remain correct.
- [ ] Responsive cards handle missing/long data.
- [ ] Revisions contain no relationship data.
