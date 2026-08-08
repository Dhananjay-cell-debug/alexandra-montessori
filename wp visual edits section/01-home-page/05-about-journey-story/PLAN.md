# Home About us journey story plan

## Intent and feel

This section should feel personal and established: a readable account of Alexandra’s careful growth paired with the founders’ circular portrait. Editing must honour its editorial rhythm—one heading and a short sequence of paragraphs—while making the source of every word/image clear.

## Current evidence

- `AboutUsBrief` renders `data-am-vb-region="home-about"` on sage-50 with 80px desktop/tablet and 56px mobile padding.
- Desktop layout is roughly 1.05fr text / 0.95fr image, aligned centre; mobile/tablet put the circular image before the text.
- Current heading is “About us”, followed by three fallback paragraphs covering Hounslow 2019, Heston 2024, Hammersmith 2025 and the four Directors.
- The image defaults to `owners.webp`, circular, shadowed and sized 448px desktop, 420px tablet, 360px mobile.
- `window.amData.about` is canonical when populated; Home visual keys can override the displayed heading/paragraphs/image.
- Milestone chips below the paragraphs are planned separately in `06-about-milestone-chips`.

## Editable elements and controls

- Section-level ownership here: visibility, pale background token, vertical padding, text/media gap, content width and circular image size per device.
- Heading text plus paragraph collection with add, split, merge, reorder, hide/archive and restore from canonical About data.
- Content-source indicator per field: “Website Content → About us”, “Home display override”, or “React fallback”; one-click return to source.
- Founders image picker/replace, alt, circular crop focal x/y, scale/offset and consent/source note.
- Text column width/alignment preset and paragraph spacing within bounded editorial values.
- Live readability/overflow checks for long copy; no arbitrary font styling inside individual paragraphs.

## Selection, layers and dragging

The section contains a text group, milestone subregion and image frame. Desktop allows constrained column swap only if an explicitly approved layout preset is chosen; current default remains text left/image right. Image drag distinguishes frame offset from crop. Paragraphs reorder in the outline, not by absolute pixel drag. Heading and paragraphs may receive small bounded optical offsets but remain in semantic flow.

## Desktop, tablet and mobile

Desktop uses the exact two-column arrangement; tablet/mobile put image first and story second, with milestones remaining after paragraphs. Device controls cover image size, gap, padding and bounded offsets. Test 320px width, 200% zoom, five paragraphs and long unbroken copy. Circle must keep the founders’ heads safely framed on every device.

## Data ownership and bindings

Canonical organisation story fields should remain in the shared About CMS record; the Home section stores presentation and explicit Home-only display overrides. Paragraphs need stable IDs rather than numeric keys so add/reorder does not misapply saved offsets. Media references a WordPress attachment. Milestones share the canonical About record but have their own presentation plan.

## Protected rules

- Preserve one section heading and valid heading order.
- Sanitise plain paragraphs; no scripts, arbitrary embeds or rich-text layouts inside this summary.
- Clamp image size/offset and section widths so the circle cannot cover text.
- Image aspect remains circular cover; replacing does not delete source media.
- Hiding/removing all story content requires explicit confirmation and must not leave a blank background band.
- Editing a Home override never silently rewrites the full About page source.

## Accessibility and failure states

The founders image receives meaningful alt if it conveys identity; do not use filenames. Paragraphs remain readable at zoom and use robust wrapping. If canonical content is temporarily unavailable, current fallback story appears. If image fails, `Img` provides a composed sage/cream fallback in the same circle. Zero valid paragraphs hides the text group only with an editor warning; heading/image/milestones reflow deliberately.

## Storage and versioning

Continue section design in the Home model while normalizing story overrides to stable paragraph IDs. Record binding mode/source revision so editors can see if canonical About changed after an override. Migrate current `about-paragraph-1..3` and `about-image` values losslessly. Revisions distinguish content-source changes, copy edits, media replacements and crop/geometry edits.

## Planned implementation files (future only)

- `src/components/home/HomeAboutJourney.jsx`
- `src/pages/Home.jsx`
- `src/lib/aboutContentModel.js`
- `wordpress/mu-plugins/alexandra-visual-builder/assets/panels/home-about-story.js`
- `wordpress/mu-plugins/alexandra-visual-builder/tests/home-about-story.php`

## Current public section → exact WP canvas parity

| Current public element | WP canvas requirement |
| --- | --- |
| Sage-50 About band | Same background and 80/80/56px initial padding |
| “About us” + current three paragraphs | Same resolved CMS/fallback copy and paragraph order |
| Text left, founders circle right on desktop | Same 1.05/0.95 layout, gap and vertical alignment |
| Founders image first on smaller screens | Same responsive order, circle, shadow and size |
| Milestone chips after paragraphs | Render exact sibling subregion from its separate plan, not duplicate it here |

## Acceptance checklist

- [ ] Untouched canvas resolves and renders the exact current story/source data.
- [ ] Source versus Home override is visible and reversible per field.
- [ ] Paragraph add/reorder uses stable identity and retains formatting/offsets.
- [ ] Responsive image order/crop matches public output and stays subject-safe.
- [ ] Missing content/media has deliberate fallback/reflow.
- [ ] Story edits never silently overwrite full About-page content.

