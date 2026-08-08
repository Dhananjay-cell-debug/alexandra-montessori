# Home About milestone chips plan

## Intent and feel

The milestone chips should turn the longer About story into a glanceable growth timeline without becoming a separate timeline graphic. Established locations appear as quiet white pills; an upcoming milestone, when explicitly supplied, receives slightly stronger sage emphasis.

## Current evidence

- Chips render inside `home-about` after the three story paragraphs.
- Current established values are “2019 - Hounslow”, “2024 - Heston” and “2025 - Hammersmith”.
- Established chips use white fill, sage-200 border, small medium text and soft shadow.
- `about-upcoming` renders only when `cmsAbout.upcoming` is non-empty; it uses a stronger sage surface/border and semibold text.
- The chip row uses flex-wrap with 0.75rem gaps, so it naturally creates additional rows.

## Editable elements and controls

- Established milestone collection: year/date label, location/name, combined display preview, add, reorder, archive and restore.
- Upcoming milestone: explicit enable toggle, label/date/place and “not yet public” staging status; empty remains absent exactly as today.
- Edit as structured fields with an optional display-label override, preventing accidental loss of date/place meaning.
- Chip style presets for established/upcoming, border, fill, radius, padding, gap and alignment within the About section.
- “Sync from published nurseries/opening dates” suggestion, never automatic without verified dates.
- Preview one, three, many, long location and no-upcoming states.

## Selection, layers and dragging

Chips reorder horizontally/vertically through collection drag handles with a keyboard alternative; the runtime still uses normal flex flow. Individual chips cannot be freely positioned or scaled into overlap. The chip group is selected separately from the story and image but remains locked after the paragraphs in semantic order.

## Desktop, tablet and mobile

Desktop and smaller screens use the same wrapping rule, with responsive gap/padding bounds. On narrow mobile, pills wrap rather than shrink text below readable size or overflow. Alignment follows the story column; current default is start-aligned. Test 320px, long labels, five milestones and 400% zoom.

## Data ownership and bindings

Canonical milestone/upcoming values come from the shared About CMS record when present. The Home milestone model stores stable record IDs, order, presentation and explicit Home display overrides. Nursery records may be suggested sources but must not create dates that are absent. The surrounding section geometry belongs to `05-about-journey-story`.

## Protected rules

- Never infer or auto-publish opening dates/upcoming locations.
- An upcoming chip requires a non-empty factual label and explicit public status.
- Keep established/upcoming visual distinction accessible and not colour-only in editor labelling.
- Clamp chip sizing/gaps; no raw HTML or links in the current public parity.
- Removing all chips hides the group without leaving reserved empty margin.

## Accessibility and failure states

Use a semantic list with a concise accessible label such as “Alexandra Montessori milestones”; preserve chronological DOM order or warn when a custom order is nonchronological. Text conveys status; do not rely solely on fill colour. Invalid dates remain plain text if explicitly intended but trigger a content warning. Missing source data falls back to the three current values; empty upcoming stays omitted.

## Storage and versioning

Migrate `about-milestone-1..3` and `about-upcoming` to stable-ID records in a future Home schema. Save source binding/override status and presentation separately. Revision history describes add/reorder/status changes. A staged upcoming milestone can be stored as draft without entering the public payload.

## Planned implementation files (future only)

- `src/components/home/HomeAboutMilestones.jsx`
- `src/lib/aboutContentModel.js`
- `wordpress/mu-plugins/alexandra-visual-builder/assets/panels/home-about-milestones.js`
- `wordpress/mu-plugins/alexandra-visual-builder/tests/home-about-milestones.php`
- `src/pages/Home.jsx`

## Current public section → exact WP canvas parity

| Current public chip state | WP canvas requirement |
| --- | --- |
| 2019 - Hounslow | Same first value and established white-pill treatment |
| 2024 - Heston | Same second value and gap/order |
| 2025 - Hammersmith | Same third value and wrapping behaviour |
| Upcoming omitted when blank | Initial canvas/public preview reserves no chip or gap |
| Upcoming sage-emphasised when present | Preview uses the exact current alternate class treatment |

## Acceptance checklist

- [ ] Current three chips and absent upcoming state match public output exactly.
- [ ] Add/reorder/archive keeps stable identity and structured facts.
- [ ] Draft upcoming data cannot leak into public output.
- [ ] Wrapping remains readable at narrow widths/zoom.
- [ ] Empty group removes its margin cleanly.
- [ ] Canonical source and Home overrides remain distinguishable.

