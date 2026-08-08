# Blog article — Not-found public section plan

## 1. Intent and feel
Handle removed or mistyped article URLs gently, explain what happened in parent-friendly language and return readers to the current archive.

## 2. Current React/public evidence and live-canvas parity
After loading completes without a post, BlogArticle.jsx returns Seo title “Article not found”, description and path /blogs, plus one container section/card with eyebrow, h1 “This article is no longer available”, body and Back to all articles link. It is mutually exclusive with loading/error/normal branches.

## 3. Exact editable elements and controls
Expose eyebrow, h1, body, Back label; typed /blogs destination; card/section background, padding, width, border/radius/shadow; text/button states; responsive styles. Page settings expose fallback SEO title/description/social image while current canonical behavior remains /blogs.

## 4. Layers and reorder rules
Section > card > eyebrow > h1 > paragraph > archive link. Semantic order is locked and the branch cannot coexist with normal article sections.

## 5. Responsive behavior
Retain max-w-3xl, p-8/p-12 and 3xl/4xl h1. Allow bounded mobile padding/type/button width and natural wrapping.

## 6. Data ownership and binding
Fallback copy/SEO/styles belong to article template. Missing status comes from fetchBlogArticle returning null/404; no removed Article content or slug is stored.

## 7. Protected functional logic
Protect !post completed branch, current Seo fallback, safe /blogs Link and early return. The design cannot force not-found on a valid Article or expose stale cached content.

## 8. Accessibility
Maintain one h1, descriptive link, visible focus, AA contrast and readable paragraph width. Avoid wording that implies user fault.

## 9. Builder state previews
Use an Article not found fixture to select the real branch; preview long text, focus/hover, contrast and devices. No deleted record is loaded.

## 10. Storage, publishing, and versioning
Store blog-article-not-found with typed internal link and SEO fallback in revisioned template snapshots. Exclude requested slug/records. Validate archive destination before publish.

## 11. Planned implementation files (future only)
Add not-found hooks/design reads to BlogArticle.jsx; register fallback schema in future class-am-vb-blog-design.php; add fixture/state inspector to assets/editor.js/canvas.css; test null/404 parity and SEO.

## 12. Acceptance checklist
- [ ] Null/404 shows this exact public branch.
- [ ] Valid Article never shows it.
- [ ] Back link and fallback SEO remain correct.
- [ ] Accessibility/mobile checks pass.
- [ ] Revisions contain no removed content.
