# Phase 2 Handoff — Universal Visual Controls

Date: 27 July 2026  
Prerequisite: Phase 1 complete on Local  
Production deployment: not authorised  
Target builder schema: version 3, with automatic version-2 migration

## Phase 2 outcome

Turn the stable Phase 1 workspace into a mature Canva-style control system for
the current primitive elements:

- rich text;
- headings and lists;
- images;
- video;
- buttons and links;
- shapes and approved SVGs;
- backgrounds and overlays;
- borders, corners and shadows;
- responsive visual overrides;
- accessibility and sanitisation feedback.

Phase 2 expands element controls. It does not migrate the website’s real routes
yet.

## Foundation inherited from Phase 1

Phase 2 must build on, not replace:

- schema migration and validation;
- multi-page records;
- feature flags;
- dedicated capabilities;
- lock ownership;
- optimistic hash conflicts;
- autosave and browser recovery;
- revisions and audit;
- reusable section templates;
- draft/review/publish workflow;
- immutable published snapshots;
- protected React preview;
- draft-safe public AST;
- Local-only activation.

Every new control must survive save, reload, revision restore, template reuse,
preview and publication.

## Implementation sequence

## Phase 2A — Rich-text and typography engine

Deliver:

- selection-aware floating text toolbar;
- bold, italic, underline and strikethrough;
- link, edit link and unlink;
- text colour and highlight colour;
- bullet and numbered lists;
- indent and outdent;
- block quote;
- clear formatting;
- paste as plain text;
- sanitised paste from Word and Google Docs;
- H1–H6, paragraph and div/rich-text semantics;
- font family from approved Alexandra presets;
- font weight and style;
- font size per breakpoint;
- optional bounded fluid font size;
- line height;
- letter and word spacing;
- alignment;
- text transform;
- text decoration;
- maximum line width;
- text shadow;
- inherited-value display and reset per breakpoint.

Implementation notes:

- Replace direct `document.execCommand` dependency with a deterministic
  selection-preserving rich-text command layer.
- Store semantic HTML separately from visual typography.
- Continue allowing only the approved inline tag and attribute set.
- Preserve the user selection while a toolbar control is used.
- Do not store arbitrary pasted classes, IDs, styles or scripts.

Exit checks:

- mixed bold/italic/underline/link combinations round-trip;
- nested lists round-trip;
- toolbar actions preserve selection;
- Word/Google Docs paste is clean;
- exactly one visible H1 produces no warning;
- missing, multiple or skipped heading levels produce actionable warnings.

## Phase 2B — Colour, backgrounds and surfaces

Deliver:

- Alexandra theme palette;
- recent custom colours;
- custom colour picker;
- foreground and background colours;
- linear and radial gradients;
- bounded gradient stops and angles;
- background image;
- image position, size and repeat;
- overlay colour/gradient and opacity;
- approved blend modes;
- border colour, width and style;
- linked/unlinked border sides;
- linked/unlinked corner radii;
- approved shadow presets;
- bounded custom box shadow;
- opacity;
- reset one value, panel or complete element to its theme default.

Schema requirements:

- represent colours in a normalized safe format;
- store gradients as structured stop/angle data, not arbitrary CSS;
- store borders and shadows as structured objects;
- migrate existing Phase 1 scalar values without changing their appearance;
- convert the structured values to bounded CSS variables in one shared
  renderer.

Exit checks:

- every preset and custom value matches editor and React preview;
- malicious CSS tokens are rejected;
- inherited/reset states are deterministic;
- invalid gradient or shadow values cannot break the style sheet;
- contrast feedback updates when foreground or background changes.

## Phase 2C — Image and video controls

### Images

Deliver:

- upload, browse, replace and remove;
- WordPress image-size selection;
- responsive `srcset`/sizes metadata;
- crop UI;
- aspect-ratio presets and custom ratio;
- focal point;
- object fit and object position;
- rotate and flip;
- border, radius and shadow;
- brightness, contrast, saturation, grayscale, sepia and bounded blur;
- overlay;
- caption;
- alt text;
- decorative-image toggle;
- internal/external/file link;
- new-tab option;
- eager/lazy loading with safe defaults;
- optional mobile image;
- separate mobile crop/focal point.

### Video

Deliver:

- WordPress MP4/WebM;
- allow-listed YouTube and Vimeo URL;
- poster and mobile poster;
- autoplay only when muted;
- loop, mute and controls;
- bounded start time;
- fit and focal point;
- background-video mode;
- overlay;
- caption/transcript link;
- reduced-motion poster fallback.

Schema requirements:

- store WordPress attachment IDs as the source of truth;
- keep derived URLs for preview fallback only;
- allow only approved external video hosts;
- reject unsafe embed HTML;
- preserve image/video data when a block is temporarily unsupported.

Exit checks:

- missing media has a useful editor placeholder;
- deleted attachment produces a readiness warning;
- decorative images do not require alt text;
- informative images require alt text;
- responsive variants render without loading the original image everywhere;
- autoplay and reduced-motion behavior are correct.

## Phase 2D — Shape, icon, button and link controls

### Shapes and icons

Deliver:

- rectangle, rounded rectangle, square, circle, ellipse, pill, triangle,
  diamond, hexagon, pentagon, star, heart, speech bubble, arch, wave, approved
  blobs, line, arrow and Alexandra leaf/sprout shapes;
- width, height and aspect ratio;
- solid or gradient fill;
- outline/border;
- radius where relevant;
- opacity;
- rotation;
- horizontal/vertical flip;
- shadow and bounded blur;
- layered X/Y and z-order compatibility;
- decorative/accessibility state;
- optional safe link/action;
- approved icon library;
- Administrator-only SVG upload;
- strict SVG sanitizer with scripts, foreign objects, external resources,
  event attributes and unsafe URLs removed.

### Buttons and links

Deliver:

- label and optional icon;
- icon position;
- internal visual page;
- existing public route;
- section anchor;
- external URL;
- WordPress document/PDF;
- phone and email actions;
- download;
- new-tab behavior with safe `rel`;
- size and full-width options;
- fill, outline and text variants;
- normal, hover, active, focus and disabled styles;
- padding, typography, radius, border, colour, gradient and shadow;
- keyboard-visible focus preview.

Exit checks:

- every shape renders from structured data;
- SVG security fixtures pass;
- unsafe URL schemes are rejected;
- focus appearance meets contrast/visibility requirements;
- button state styles match editor and React preview;
- empty or inaccessible labels block publication.

## Phase 2E — Responsive inheritance, readiness and regression

Deliver:

- explicit inherited-value indication;
- override/reset for Desktop, Tablet and Mobile;
- breakpoint visibility;
- minimum readable text warning;
- tap-target size warning;
- colour contrast meter;
- missing-alt/decorative validation;
- missing button accessible-name validation;
- unsupported-media validation;
- font-loading fallback;
- phase-wide paste and XSS sanitisation fixtures;
- schema-2 to schema-3 migration tests;
- template and revision round-trip tests for every new element attribute;
- crash-recovery tests with the new schema;
- public AST allow-list tests;
- editor/public rendering-parity fixtures.

## Schema version 3 shape

Keep existing page, section, group and element identity fields.

Add structured attributes under approved namespaces:

- `content`: semantic element content;
- `typography`: font and text presentation;
- `surface`: fill, gradient, border, radius, shadow and opacity;
- `media`: attachment, crop, focal point, filters, caption and loading;
- `interaction`: link/action and approved states;
- `accessibility`: alt/decorative/accessible name;
- `responsive.desktop`;
- `responsive.tablet`;
- `responsive.mobile`.

Requirements:

- one-way, idempotent migration from schema 2;
- old Phase 1 documents preserve their rendered appearance;
- unknown attributes remain preserved in a quarantined migration payload;
- the public normalizer emits only approved fields;
- arbitrary CSS, HTML attributes, JavaScript and embed code remain prohibited.

## Code ownership map

Primary Phase 2 areas:

- `wordpress/mu-plugins/alexandra-visual-builder/includes/`
  - schema migration and sanitisation;
  - readiness rules;
  - normalized public AST.
- `wordpress/mu-plugins/alexandra-visual-builder/assets/editor.js`
  - selection toolbar;
  - visual controls;
  - media/shape/button workflows;
  - responsive inheritance and resets.
- `wordpress/mu-plugins/alexandra-visual-builder/assets/editor.css`
  - workspace controls and accessibility states.
- `wordpress/mu-plugins/alexandra-visual-builder/assets/canvas.css`
  - shared safe rendering primitives.
- `src/components/VisualBuilderPreview.jsx`
  - schema-3 primitive renderer.
- new isolated renderer/control modules should replace the Phase 1 monolithic
  editor file incrementally before it becomes difficult to maintain.

## Required automated test matrix

### Persistence

- every Phase 2 field saves and reloads;
- autosave and manual save produce the same normalized result;
- revision restore reproduces exact element data;
- reusable-section insertion regenerates IDs without losing controls;
- schema migration is idempotent.

### Rich text

- all formatting combinations;
- nested lists;
- links and unlink;
- paste from Word and Google Docs;
- disallowed tags/attributes removed;
- selection preserved during toolbar use.

### Media

- valid, missing and deleted attachment;
- empty and long alt text;
- decorative image;
- every crop and fit mode;
- desktop/mobile sources;
- external video allow list;
- reduced-motion fallback.

### Shapes and SVG

- every supported shape;
- extreme but bounded dimensions;
- fill/gradient/border/shadow;
- malicious SVG corpus;
- inaccessible linked shape rejection.

### Responsive

- inheritance and reset;
- Desktop/Tablet/Mobile overrides;
- hide/show behavior;
- no horizontal overflow from a malformed value;
- rendering checks at 360, 390, 768, 1024 and 1440 px.

### Security

- script/event-handler HTML;
- unsafe links;
- CSS expression and JavaScript URLs;
- SVG script, external resource and foreign-object attempts;
- malformed imported schema;
- unauthorised draft/media/template actions;
- public AST contains no private attributes.

### Regression

- Phase 1 lock and conflict tests;
- crash recovery;
- immutable published snapshot;
- normal Home contains no draft payload;
- editor and protected preview load;
- React lint and WordPress build;
- no Local PHP fatal.

## Phase 2 acceptance gate

Phase 2 is complete only when:

- every listed primitive control is usable by a non-technical client;
- every field survives save, reload, revision restore and reusable-template
  insertion;
- editor and React preview match at all three breakpoints;
- unsafe markup, CSS, URLs, embeds and SVGs are rejected;
- accessibility/readiness feedback is actionable;
- the Phase 1 concurrency, recovery and publishing guarantees still pass;
- a manual Local Chrome walkthrough completes the client tasks;
- no production route or database is changed.

## Explicit non-goals

These stay in later phases:

- nested container/flex/grid engine, snap guides and complete freeform canvas:
  Phase 3;
- cards, galleries, tabs, accordions, carousels and interactions: Phase 4;
- Header, navigation, menus, Footer and global design system: Phase 5;
- Nursery, Blog, Events, Jobs and other dynamic collection bindings: Phase 6;
- form builder: Phase 7;
- real route-by-route content migration: Phases 8–16;
- production activation: Phase 18 and only after explicit approval.

## Start condition

Begin Phase 2A on Local only. Preserve the Phase 1 tests as mandatory
regression gates and add schema-3 fixtures before changing the stored control
model.
