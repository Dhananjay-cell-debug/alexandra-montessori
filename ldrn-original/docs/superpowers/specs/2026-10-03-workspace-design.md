# LDRN visual workspace

## Requested result

Give Little Rainbows staff the INU-style WordPress editing experience on the
new `lrdn.local` installation. Preserve the current LDRN source, including the
2 October photographs. The full scope includes pages, layers, draggable
assets and media, at least 30 nursery section templates, responsive property
editing, reusable sections, content collections, publishing, recovery and logs.

## Decision and alternatives

Use the proven INU canvas interactions in an independently namespaced LDRN
workspace, integrated with the existing LDRN theme and WordPress content model.
The existing LDRN source lives in `../frontend` and `../wordpress`; the backup
is recovery evidence rather than the current implementation baseline.

A direct copy of the entire INU plugin would import its agency content,
enquiry handling and static-build assumptions. A completely new canvas engine
would duplicate years of interaction work. Reusing the canvas while replacing
its brand, library, page registry and integrations is the selected approach.

## Editor

Use a dark, focused three-panel workspace. The LDRN public design keeps its
own fonts, colours, photographs and components. Pages manages built-in and
new routes; Layers exposes nested element selection, names, visibility,
ordering and duplication; Assets contains section templates, single elements
and user-saved sections; Media exposes shipped images and WordPress uploads.

The canvas offers desktop, tablet and phone widths, zoom, preview mode,
inline text editing, drag/resize, multi-selection, undo and redo. The inspector
shows content, typography, media crop/alt text/link, size, layout, spacing,
position, background, borders, shadow and visibility as applicable to the
selection. Styles inherit from desktop; tablet and phone overrides are explicit
and resettable. Interactive forms keep their existing application behaviour.

## Content and durability

WordPress remains the source for events, articles, nursery records,
testimonials, jobs and gallery media. Dynamic item identities use record IDs
or slugs rather than collection positions. New pages use the shared header
and footer. Source additions register editable identities and reuse the same
inspector; they do not require a second editor implementation.

Visual documents store element edits and inserted trees independently of
the frontend build. Named stable source anchors take precedence over positional
addresses; legacy position edits carry fingerprints and fail visibly if their
target is gone. Reusable sections create independent editable copies initially;
their source template remains available for later insertions.

## Publishing and recovery

Draft saves must not change visitor output. Publishing creates a revision
and a saved session. Revision checks prevent silently replacing another
editor's draft. Failed saves remain recoverable locally, token renewal handles
long-lived sessions, and restoring a session first records a recovery copy.
Audit records capture actor, time and operation without storing submission
contents. Page changes, templates and visual styles must survive updates.

## Thirty section templates

1. Welcome hero
2. Split photo hero
3. Page introduction
4. Nursery overview
5. Nursery feature cards
6. Learning through play
7. EYFS learning areas
8. Daily routine
9. Baby room
10. Toddler room
11. Preschool room
12. Photo gallery grid
13. Photo and caption
14. Nursery contact details
15. Opening hours
16. Visit invitation
17. Availability invitation
18. Admissions steps
19. Fees introduction
20. Funding information
21. Download resources
22. Questions and answers
23. Parent stories
24. Event introduction
25. Upcoming events
26. Article introduction
27. Latest articles
28. Careers introduction
29. Vacancy invitation
30. Closing call to action

Templates carry responsive defaults, real site assets and editable child
elements. Starter copy is clearly editable; templates never fabricate prices,
ratings, testimonials or staff biographies. Collection sections consume current
WordPress records rather than taking a one-time copy of a list.

## Delivery sequence and acceptance

1. Back up the fresh local installation; install the LDRN theme and content
   plugins; seed only the source's confirmed baseline. Check all existing
   routes and asset references.
2. Integrate the editor and 30 templates. Verify select, inline edit, inspector,
   insert, media replace, breakpoint overrides, undo, draft isolation and publish.
3. Complete content workflows, article detail routes, live collection sections,
   gallery editing and reusable user sections. Verify newly published records
   appear without a build.
4. Verify session restore, conflicts, expired tokens, capabilities, sanitization,
   route visibility and responsive visitor rendering. Save evidence and a staff
   handover guide.

Completion requires real browser verification of the client workflow at all
three canvas widths, as well as server and build checks. Chrome is connected;
the live audit covers 21 routes, and browser workflow verification is ongoing.

The INU reference was inspected through its Pages, Assets, Media and All saved
sessions controls, including a saved website preview. Sessions must preserve
the actual frontend build and public nursery records alongside design, pages,
navigation and reusable sections. Preview navigation stays inside the saved
version; preview forms and tracking are disabled. The saved-session screen uses
INU's full-screen shell, inline named saves, search and nearest-date ordering.
Restore this version first asks for confirmation, saves recovery of the current
live website and any unpublished draft, then restores the complete saved site
immediately and returns to All saved sessions, matching INU's flow.
Operational records, users, enquiries, credentials and notification routing
are excluded from design recovery. Legacy design-only saves are clearly marked.
