# Blog archive — Results, cards, request states and pagination plan

## 1. Intent and feel
Make the archive feel dependable and easy to browse: users always understand what is showing, errors are recoverable, and the client can shape card presentation without compromising live filters or article data.

## 2. Current React/public evidence and live-canvas parity
Blogs.jsx renders one archive section with aria-busy. It shows “Article archive” and a polite result line: loading, Showing x–y of total, or No matching articles. Error renders a card with retry button; ready items render ArticleCard grid; ready empty renders a no-date card/link; multi-page data renders Previous/Page x of y/Next navigation. ArticleCard binds image, category, date, title, excerpt and Read more. Requests are keyed by year:month:page:reloadToken and AbortController cancels stale work. Canvas must exercise this exact section and branches.

## 3. Exact editable elements and controls
Expose archive label and result sentence templates with protected {first}/{last}/{total}; section spacing; grid columns/gap; card shell/image/category/date/title/excerpt/action/icon styles; date format; error title/body/Try again label; empty title/body/View full archive label; pagination Previous/Next/Page pattern, button states and spacing. Provide Article fixture selector and Manage Articles link; record fields are read-only tokens.

## 4. Layers and reorder rules
Section > header row > archive label/result status > mutually exclusive error, cards, or empty branch > optional pagination. Card layers are image > metadata > title > excerpt > action inside one whole-card link. Error retry stays in error card; empty reset link stays in empty card; pagination remains after results. Record sort/order and page slices cannot be dragged in Layers.

## 5. Responsive behavior
Retain one-column mobile, two-column md, three-column lg card grid. Allow bounded grid/card/image/text settings per device. Header row wraps, long titles/excerpts/categories remain readable, and pagination buttons wrap without reversing logical order.

## 6. Data ownership and binding
Articles/archive endpoint owns featured exclusion, items, filters, total, page, perPage, totalPages, slug, image, category, date, title and excerpt. Shell labels/templates/styles belong to /blogs design. Current local preview uses generatedBlogs and 9 items per page; WordPress response is authoritative when blogApiUrl exists.

## 7. Protected functional logic
Protect request keying, AbortController, retry token, URL-preserving pageHref, result range maths, conditional branches, whole-card routes, page bounds and Arrow icon behavior. Do not let style edits suppress aria-busy or turn bound counts into static numbers. Error retry must call the real fetch path in public; builder preview intercepts it. No separate invented loading skeleton is added.

## 8. Accessibility
Keep aria-busy, polite result status, nav aria-label, descriptive previous/next labels, visible focus and semantic headings. Cards have meaningful alt and one link each. On state changes, announce results/error without unexpectedly moving focus; retry is a real button.

## 9. Builder state previews
Provide real-render fixtures for initial ready grid, filter-query loading with no current archive, error, retry transition, ready empty, one result, nine results, multiple pages, first/middle/last page, missing optional image/category/excerpt and long content. State controls are editor-only and never fetch unless Live data is explicitly selected.

## 10. Storage, publishing, and versioning
Store blogs-article-archive shell, allow-listed tokens and responsive styles in /blogs revisions; exclude Article/results/query/fixture data. Version card slot order and state-copy schema; published snapshots remain compatible with archive API fields and fall back safely. Audit changes to error/retry/pagination copy.

## 11. Planned implementation files (future only)
Instrument Blogs.jsx/ArticleCard with stable section, branch, card-template and slot hooks; add token/binding sanitization to future class-am-vb-blog-design.php; build Layers/Record/States inspector in assets/editor.js and canvas state CSS; test aborts, retry, filters, ranges, pagination, routes and public parity.

## 12. Acceptance checklist
- [ ] All archive states come from the real Blogs section branches.
- [ ] Articles remain authoritative and read-only in visual editing.
- [ ] Filtering, retry, counts, cards and pagination behave unchanged.
- [ ] State changes are announced and keyboard usable.
- [ ] Canvas/live match across fixtures/devices.
- [ ] Revisions contain no article lists or query state.
