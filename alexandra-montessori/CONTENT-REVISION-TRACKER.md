# Content Revision Tracker

Date: 2026-07-15
Project: `alexandra-montessori`
Purpose: Execute the requested nursery and curriculum content changes in controlled phases, updating this file after each completed step.

## Working Rules

1. Edit only the live app in `alexandra-montessori/`.
2. Complete one phase at a time.
3. After each phase:
   - update this tracker,
   - verify the affected page/section,
   - record anything still pending.
4. Prefer existing site assets unless a new asset is required.

## Target Files

- `src/pages/Nurseries.jsx`
- `src/pages/NurseryDetail.jsx`
- `src/pages/Home.jsx`
- `src/pages/Curriculum.jsx`
- `src/data/site.js`

## Phase Board

### Phase 1 - Setup and mapping

Status: `COMPLETED`

Scope:
- map the requested changes to the live source files,
- create this tracker before code edits,
- define verification points.

Verification:
- confirm each requested section has a clear source file,
- confirm all later work will happen inside `alexandra-montessori/`.

Result:
- mapped files:
  - nursery overview: `src/pages/Nurseries.jsx`
  - nursery detail pages: `src/pages/NurseryDetail.jsx`
  - home testimonials insertion point: `src/pages/Home.jsx`
  - curriculum page: `src/pages/Curriculum.jsx`
  - branch-specific text/cards: `src/data/site.js`

Remaining:
- none.

### Phase 2 - Nursery overview page copy

Status: `COMPLETED`

Scope:
- replace the current brief on the `Our Nurseries` page with the supplied copy from Image #1.

Verification:
- page heading remains `Our Nurseries`,
- replacement copy appears with the new Hounslow/Heston/Hammersmith wording,
- age wording reflects the supplied copy exactly where requested.

Result:
- replaced the short single-paragraph intro with the supplied two-paragraph brief,
- preserved the existing section structure and card grid below it.

### Phase 3 - Nursery detail page updates

Status: `COMPLETED`

Scope:
- change nursery hero age wording to `Babies to 5 Years`,
- change the Hounslow and Heston `What we offer` heading to:
  - `Everything your child needs to achieve, thrive and belong in Hounslow`
  - `Everything your child needs to achieve, thrive and belong in Heston`
- Hammersmith:
  - remove the meals section,
  - remove `Outdoor garden play`,
  - rename `Authentic Montessori` to `Montessori Inspired`
- Heston:
  - remove `Creative atelier`,
  - add `Ample Parking`

Verification:
- all three nursery hero intros show the intended age wording,
- Hammersmith has no meals section,
- Hammersmith feature grid reflects the requested removals/rename,
- Heston feature grid reflects the requested replacement,
- Hounslow/Heston headings use the new sentence.

Result:
- switched the nursery hero age line to the shared `Babies to 5 Years` wording,
- added per-branch feature-grid control through the data layer,
- removed the Hammersmith meals section,
- updated Hammersmith and Heston feature cards as requested,
- updated Hounslow and Heston `What we offer` headings.

### Phase 4 - Home page about section insertion

Status: `COMPLETED`

Scope:
- insert a new brief/about section between the three-pointer benefits row and `What Parents Say`,
- use the supplied copy from Image #4,
- choose a suitable non-repetitive photo from existing site assets,
- make the section visually intentional and consistent with the site.

Verification:
- section appears after the three benefits row and before testimonials,
- copy matches the supplied brief,
- image is relevant and not redundant with the testimonials background image.

Result:
- inserted a new home/about section between the benefits row and testimonials,
- used the supplied brief copy,
- paired it with `friends-two.webp` to keep the section warm and distinct from the testimonials background.

### Phase 5 - Curriculum page rewrite

Status: `COMPLETED`

Scope:
- replace `Our Philosophy` body copy with the supplied text from Image #6,
- retitle the second main section from `Our Curriculum` to `The EYFS`,
- replace its body copy with Image #8,
- remove the `Healthy Meals & Nutrition` section,
- add two replacement sections using the supplied text from Image #10,
- choose suitable non-repetitive images for the new replacement sections.

Verification:
- philosophy section uses the new copy,
- second section title is `The EYFS`,
- meals section is gone,
- both replacement sections render with image + copy and feel consistent,
- no repeated image use that weakens the page.

Result:
- replaced the philosophy text with the supplied copy,
- retitled the second section to `The EYFS` and replaced its body content,
- removed the meals section entirely,
- added `The Montessori Approach` and `Where They Meet` as image-and-copy sections,
- assigned distinct existing site images across the page to avoid repetition.

### Phase 6 - Build verification and final tracker update

Status: `COMPLETED`

Scope:
- run the production build,
- fix any compile/runtime issues caused by the edits,
- update this tracker with completed phases and any residual notes.

Verification:
- `npm run build` completes successfully,
- tracker reflects completed work and anything intentionally left unchanged.

Result:
- `npm run build` completed successfully on 2026-07-15,
- no build errors were introduced by the content or layout changes.

### Phase 7 - Post-review visual fixes

Status: `COMPLETED`

Scope:
- remove the remaining `Creative atelier` card from Hammersmith,
- lower the `Where They Meet` image framing slightly so the child's face is not cropped at the top,
- replace the repeated home about-section image with a different existing asset.

Verification:
- Hammersmith no longer shows `Creative atelier` in its feature grid,
- the curriculum `Where They Meet` image shows more headroom,
- the home about section no longer uses the repeated `friends-two.webp` image.

Result:
- extended the Hammersmith feature filter to remove the leftover `Creative atelier` card,
- added a targeted image-position override for the curriculum `Where They Meet` photo,
- swapped the home about image to `painting-side.webp` for a less repetitive visual.

### Phase 8 - Home about heading simplification

Status: `COMPLETED`

Scope:
- remove the small `About us` pill above the home about section,
- replace the large heading with `About us`.

Verification:
- the home about section no longer shows the pill-style label,
- the section heading reads `About us`.

Result:
- removed the eyebrow pill from the home about section,
- updated the main heading text to `About us`.

### Phase 9 - Nursery heading and feature consistency

Status: `COMPLETED`

Scope:
- make the nursery `What we offer` heading render more consistently for Hounslow,
- change shared `Authentic Montessori` wording to `Montessori Inspired`,
- replace `Creative atelier` with `Ample parking` across all nurseries,
- keep `Ample parking` visible on Hammersmith as requested.

Verification:
- the nursery feature heading uses a wider heading container,
- all nursery feature grids show `Montessori Inspired`,
- no nursery feature grid shows `Creative atelier`,
- all nursery feature grids can show `Ample parking`,
- Hammersmith still excludes meals, onsite chef and outdoor garden play only.

Result:
- added a configurable section-heading width and widened the nursery features heading,
- updated the shared nursery feature list to use `Montessori Inspired`,
- replaced `Creative atelier` with `Ample parking` in the shared nursery feature set,
- preserved the Hammersmith-specific removals while keeping `Ample parking` available there,
- updated related marketing copy from `Authentic Montessori` to `Montessori-inspired` in the live source.

## Progress Log

- 2026-07-15: Tracker created. File mapping completed. No code changes made yet.
- 2026-07-15: Phase 1 completed. Work moved to the `Our Nurseries` page copy update.
- 2026-07-15: Phase 2 completed. Work moved to nursery detail hero, feature-grid and branch-specific section changes.
- 2026-07-15: Phase 3 completed. Work moved to the home page about/brief section before testimonials.
- 2026-07-15: Phase 4 completed. Work moved to the curriculum page rewrite and section replacement.
- 2026-07-15: Phase 5 completed. Curriculum page content and replacement sections were rewritten.
- 2026-07-15: Phase 6 completed. Production build passed cleanly.
- 2026-07-15: Phase 7 completed. Post-review visual fixes were applied to Hammersmith, the curriculum page and the home about section.
- 2026-07-15: Phase 8 completed. The home about section heading was simplified to `About us`.
- 2026-07-15: Phase 9 completed. Nursery feature labels and heading width were aligned across the nursery pages.
