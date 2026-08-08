# Alexandra Montessori — Canva-Style Visual Theme Builder Plan

Prepared: 27 July 2026  
Status: authoritative plan; Phase 0 and Phase 1 are complete on Local. Phase 2 is next.  
Deployment status: Local implementation only. No public website or production WordPress data has been changed.

## 1. Revised outcome

Build a visual website editor inside WordPress that feels closer to Canva than to a collection of ordinary form fields.

The client should be able to:

- open any website page in a visual editing canvas;
- click text and edit it directly where it appears;
- format selected text as bold, italic, underline, strikethrough, links, numbered lists or bullet lists;
- change font, weight, size, line height, letter spacing, alignment, case, decoration and color;
- add, duplicate, copy, paste, move, group, hide, lock, unlock and delete elements;
- add, duplicate, move, hide and delete complete sections;
- drag sections and cards into a new order;
- add new approved sections and elements from a visual library;
- replace, crop, resize, position, filter and style images;
- edit logos and use different logo versions in the header, footer and social sharing;
- add and edit buttons, tabs, accordions, cards, galleries, icons, dividers and shapes;
- change backgrounds, gradients, overlays, borders, corners, shadows and opacity;
- resize and align containers, columns and grids;
- control desktop, tablet and mobile presentation;
- edit hover, active and selected states where they exist;
- edit the header, desktop navigation, mobile menu, parent-information menu and footer visually;
- edit every existing page and every dynamic detail-page template;
- preview changes, undo/redo, compare revisions and restore an earlier version;
- save drafts without changing the public website;
- publish after readiness and responsive checks.

This is no longer only a “content CMS”. It is a custom visual theme-builder product built for this website.

## 2. Important interpretation of “Canva-style”

The visual experience should be Canva-like:

- left-side element/template library;
- central live canvas;
- click-to-select;
- inline text editing;
- drag-and-drop;
- resize handles where appropriate;
- layer list;
- contextual toolbar;
- detailed right-side properties;
- device previews;
- undo/redo and revision history.

The public website is responsive, unlike a fixed Canva poster. Therefore the editor needs two layout modes:

### Responsive flow mode

The default mode for almost every section.

- Elements live in stacks, rows, columns and grids.
- Moving or resizing an element updates responsive layout rules.
- Content stays readable across desktop, tablet and mobile.
- This mode should be used for forms, cards, text sections, archives and most page content.

### Freeform layered mode

An advanced section type for decorative/hero compositions.

- Elements can overlap and use X/Y positions, layers, rotation and opacity.
- Coordinates are stored as percentages within a bounded canvas.
- Desktop, tablet and mobile positions can be adjusted independently.
- DOM reading order remains separately defined for accessibility.
- The editor warns when an element goes outside the safe area.

This gives the client Canva-like freedom without forcing every responsive page into fragile absolute positioning.

## 3. Architecture

## 3.1 Use WordPress’s block-editor platform as the editing engine

Build a custom full-screen editor inside `Website Content`, using the WordPress block-editor packages rather than writing selection, drag/drop, rich text, keyboard navigation and undo from zero.

The editor will use:

- `BlockEditorProvider`;
- `BlockCanvas`;
- `BlockInspector`;
- `BlockControls`;
- `RichText`;
- `InnerBlocks`;
- media upload/replace controls;
- the inserter;
- block movers;
- keyboard shortcuts;
- WordPress autosave/revision infrastructure.

The interface will be branded and simplified for Alexandra Montessori. It will not expose the entire unrestricted Gutenberg library.

## 3.2 Dedicated visual-builder must-use plugin

Create a new `alexandra-visual-builder` MU plugin.

Responsibilities:

- visual editor admin application;
- custom block registration;
- page/global-template storage;
- style schema;
- responsive values;
- sanitisation and validation;
- revisions and autosaves;
- draft preview;
- public block-tree REST contract;
- migration utilities;
- permission and lock rules;
- editor/frontend contract tests.

Keep it separate from:

- the React theme renderer; and
- Alexandra Operations, which owns submissions, routing, notifications and private CV files.

## 3.3 Storage model

Create revisioned, protected singleton records:

- one `am_visual_page` record per page/template;
- one `am_visual_global` record for each global template:
  - Header;
  - Mobile Menu;
  - Parent Information Menu;
  - Footer;
  - Default CTA;
  - Brand Styles;
  - Cookie UI;
  - Form Styles and Messages;
  - System States.

Each record stores:

- Gutenberg-compatible serialized block content;
- immutable template key;
- fixed route or template binding;
- builder schema version;
- draft/published status;
- author and timestamps;
- revision history;
- publish-readiness results.

Clients may delete all visible blocks from a record, but they may not delete the underlying route/template record itself. This preserves routing and recoverability while still allowing a completely blank or rebuilt design.

## 3.4 Frontend rendering

The React site will render a normalised block tree.

Process:

1. WordPress parses stored blocks.
2. The plugin validates every block and attribute against its current schema.
3. Private/admin-only attributes are removed.
4. Dynamic collection bindings are resolved or described safely.
5. The browser receives a versioned page/global block tree.
6. React maps each allowed block name to an Alexandra frontend component.
7. Style attributes become bounded CSS variables/classes.

Never send arbitrary JavaScript, arbitrary CSS or unsanitised block HTML to the public renderer.

Unknown or outdated blocks:

- display an exact warning in the editor;
- render a safe placeholder in draft preview;
- cannot silently disappear from a publish without acknowledgement;
- retain their stored data for migration/recovery.

## 3.5 One renderer for editor and public site

The editor preview and public website must share:

- the same block component definitions;
- the same CSS variables;
- the same breakpoints;
- the same typography;
- the same image fitting;
- the same dynamic-data fixtures;
- the same visibility rules.

This is necessary for real WYSIWYG behavior. An admin approximation that looks different from the website is not acceptable.

## 3.6 Existing collections remain structured

Keep:

- Nurseries;
- Blog;
- Events;
- Testimonials;
- Jobs;
- Media;
- Submissions.

The visual builder adds dynamic collection blocks such as:

- Nursery Grid;
- Nursery Details;
- Testimonial Feed;
- Blog Feed;
- Event Feed;
- Vacancy Feed;
- Fee Downloads;
- Food Hygiene Cards.

The client styles, positions, filters and labels these blocks visually. The underlying collection records remain single sources of truth.

## 3.7 Feature flags and incremental route migration

Each route/template has:

- `legacy` renderer;
- `visual-builder` renderer;
- draft-preview override.

Pages migrate individually. If a page is not ready, its current React page remains active.

Production can revert a route to the legacy renderer without deleting the new block data.

## 4. Visual editor interface

## 4.1 Top toolbar

- Website/Page selector.
- Edit Global Header/Footer shortcut.
- Desktop/Tablet/Mobile preview.
- Custom viewport-width preview.
- Portrait/Landscape device orientation.
- Zoom: Fit, 25%, 50%, 75%, 100%, 125%, 150%.
- Undo.
- Redo.
- Version history.
- Preview in new tab.
- Save draft.
- Request review.
- Publish/Update.
- Page readiness indicator.
- More menu: duplicate page design, export/import design JSON, restore template default.

## 4.2 Left panel

Tabs:

1. **Pages**
   - Main navigation pages.
   - Nursery templates.
   - Parent-information pages.
   - Dynamic detail templates.
   - Legal/system pages.
   - Global components.
2. **Add**
   - Layout.
   - Text.
   - Media.
   - Buttons and navigation.
   - Cards and content.
   - Shapes and decoration.
   - Dynamic collections.
   - Forms.
   - Reusable sections.
3. **Layers**
   - Nested tree.
   - Search layers.
   - Rename layers.
   - drag reorder;
   - visibility;
   - lock;
   - select parent;
   - group/ungroup.
4. **Media**
   - WordPress library.
   - Alexandra collections.
   - recent uploads;
   - image search by filename/alt/collection.
5. **Templates**
   - approved Alexandra section patterns;
   - saved personal sections;
   - synced global sections.

## 4.3 Central canvas

- Exact website width and styles.
- Click element to select.
- Double-click text to edit.
- Drag block/section to reorder.
- Drop zones between and inside sections.
- Resize handles for supported blocks.
- Alignment guides.
- Smart spacing guides.
- Safe-area guides in layered sections.
- Breadcrumb showing Section → Container → Column → Element.
- Empty-state Add button.
- Multi-select with Shift.
- Context menu.

## 4.4 Contextual toolbar

Depending on selection:

- change block type;
- drag handle;
- move up/down;
- text formatting;
- alignment;
- link;
- crop;
- replace media;
- duplicate;
- copy;
- paste;
- copy style;
- paste style;
- group/ungroup;
- lock/unlock;
- hide/show;
- delete.

## 4.5 Right properties panel

Tabs:

1. Content.
2. Style.
3. Layout.
4. Responsive.
5. Interaction.
6. Data.
7. Accessibility.
8. Advanced.

Only controls relevant to the selected element appear.

## 4.6 Editing conveniences

- Autosave.
- Undo/redo across content and style changes.
- Revision comparison.
- Keyboard shortcuts.
- Duplicate via shortcut.
- Copy/paste between pages.
- Copy/paste only styles.
- Reset one control.
- Reset a panel.
- Reset element to theme default.
- Save selection as reusable section.
- Replace a reusable section with a local copy.
- Helpful warnings rather than silent failures.

## 5. Complete control catalogue

## 5.1 Rich text controls

For headings, paragraphs, list items, quotes, captions, labels and buttons:

- bold;
- italic;
- underline;
- strikethrough;
- text and background highlight;
- inline link;
- remove link;
- superscript;
- subscript;
- inline code only where appropriate;
- bullet list;
- numbered list;
- nested list indentation;
- block quote;
- clear formatting;
- paste as plain text;
- special characters;
- optional non-breaking-space insertion.

Per-block text settings:

- semantic tag: H1–H6, paragraph, label, span or div where valid;
- font family;
- variable-font axis where supported;
- font weight;
- font style;
- desktop/tablet/mobile font size;
- fluid-size option;
- line height;
- letter spacing;
- word spacing;
- text alignment;
- vertical alignment;
- text transform;
- text decoration;
- text color;
- selection/highlight color;
- maximum line width;
- text columns for long editorial content;
- optional line clamp;
- text shadow;
- responsive visibility.

Heading-readiness checks:

- exactly one visible H1 is recommended;
- heading levels should not skip;
- empty headings produce warnings;
- visual style can change independently from semantic heading level.

## 5.2 Layout controls

- layout type: block, flex row, flex column, grid, layered;
- content width: boxed, wide, full width, custom;
- width, minimum width and maximum width;
- height, minimum height and maximum height;
- aspect ratio;
- flex grow/shrink/basis;
- direction;
- wrap;
- justify content;
- align items;
- align self;
- row and column gaps;
- grid columns;
- grid rows;
- column span;
- row span;
- auto-fit/auto-fill grid behavior;
- order;
- margin on all sides or individual sides;
- padding on all sides or individual sides;
- overflow;
- sticky positioning for supported components;
- horizontal/vertical alignment;
- responsive stacking order;
- mobile reverse-order option;
- full-bleed background with contained content.

Values support:

- theme presets;
- custom bounded values;
- px, rem, em, %, vw and vh where appropriate;
- separate desktop/tablet/mobile settings;
- link/unlink sides for spacing and borders.

## 5.3 Background and surface controls

- background color;
- custom color picker;
- saved theme palette;
- linear gradient;
- radial gradient;
- gradient angle/stops;
- background image;
- background video for supported hero/section blocks;
- image position;
- image fit;
- repeat/no-repeat;
- fixed/scroll behavior where safe;
- overlay color;
- overlay gradient;
- overlay opacity;
- backdrop blur;
- foreground opacity;
- blend mode from an approved set;
- section pattern/dither toggle;
- surface preset.

## 5.4 Border, corner and shadow controls

- border color;
- border width;
- border style;
- individual border sides;
- linked/unlinked corner radius;
- pill;
- circle;
- square;
- rounded-card presets;
- outline;
- box shadow;
- multiple approved shadow presets;
- custom shadow X/Y/blur/spread/color;
- inset shadow;
- hover shadow;
- focus outline preview.

## 5.5 Shape controls

Shape library:

- rectangle;
- rounded rectangle;
- square;
- circle;
- ellipse;
- pill;
- triangle;
- diamond;
- hexagon;
- pentagon;
- star;
- heart;
- speech bubble;
- arch;
- wave;
- blob presets;
- line;
- arrow;
- decorative leaf/sprout shapes;
- custom approved SVG uploaded by an Administrator.

Shape properties:

- width/height;
- aspect ratio;
- fill;
- gradient;
- outline;
- border;
- radius where relevant;
- opacity;
- rotation;
- flip horizontal/vertical;
- shadow;
- blur;
- X/Y position in layered mode;
- front/back layer order;
- link/action;
- decorative/accessibility flag;
- desktop/tablet/mobile visibility;
- animation.

Client SVG uploads are not rendered without sanitisation. Raw SVG code is never pasted into a field.

## 5.6 Image controls

- upload;
- choose from Media;
- replace;
- remove;
- duplicate;
- crop;
- rotate;
- flip;
- focal point;
- object fit: cover, contain, fill;
- aspect ratio;
- width/height;
- border/radius;
- shadow;
- opacity;
- brightness;
- contrast;
- saturation;
- grayscale;
- sepia;
- blur;
- overlay;
- caption;
- alt text;
- decorative toggle;
- link;
- open in new tab;
- eager/lazy load selection with readiness rules;
- responsive image selection;
- separate mobile image where justified;
- responsive position/crop;
- image quality/size recommendation.

The frontend uses WordPress-generated responsive sizes instead of sending the original upload everywhere.

## 5.7 Video controls

- WordPress-hosted MP4/WebM;
- YouTube/Vimeo from an allow-listed URL;
- poster image;
- mobile poster;
- autoplay;
- loop;
- mute;
- controls;
- start time;
- end time where supported;
- fit and focal point;
- background-video mode;
- overlay;
- play-button style;
- caption/transcript link;
- reduced-motion fallback;
- responsive visibility.

## 5.8 Button and link controls

- text/rich text;
- icon;
- icon position;
- internal page;
- dynamic record;
- section anchor;
- external URL;
- document/PDF;
- phone;
- email;
- open new tab;
- download;
- alignment;
- size;
- width/full width;
- fill/outline/text style;
- background/text/border color;
- gradient;
- radius/shape;
- padding;
- font;
- shadow;
- hover colors;
- hover transform;
- active state;
- focus state preview;
- disabled appearance where applicable.

## 5.9 Tabs and accordion controls

- add/delete/duplicate tab;
- reorder tabs;
- tab title and rich content;
- icon/image;
- horizontal/vertical orientation;
- desktop-to-accordion mobile transformation;
- selected-tab default;
- tab width/alignment;
- active/inactive/hover styles;
- indicator line/pill/card style;
- border/radius/shadow;
- deep-link anchor;
- keyboard arrow navigation;
- accordion single/multiple-open behavior.

## 5.10 Cards and repeaters

- add;
- duplicate;
- delete;
- drag reorder;
- convert layout;
- image;
- icon;
- eyebrow;
- heading;
- rich text;
- metadata;
- buttons;
- card link;
- card background;
- border/radius/shadow;
- hover style;
- equal-height toggle;
- grid/list/carousel presentation;
- columns per breakpoint.

## 5.11 Animation and interaction

Approved interactions:

- fade;
- slide;
- scale;
- reveal;
- stagger;
- hover lift;
- hover zoom;
- hover color;
- button micro-animation;
- accordion/tabs;
- gallery lightbox;
- modal trigger;
- carousel;
- sticky header;
- scroll-to-anchor.

Controls:

- duration;
- delay;
- easing;
- trigger;
- replay once/always;
- stagger;
- desktop/tablet/mobile;
- reduced-motion fallback.

Arbitrary JavaScript and arbitrary event handlers remain prohibited.

## 5.12 Responsive controls

Breakpoints use the actual website values.

For every supported visual property:

- inherit from larger breakpoint;
- override on tablet;
- override on mobile;
- reset override;
- preview current breakpoint;
- show/hide per breakpoint;
- change stacking;
- change alignment;
- change gap and spacing;
- change font size/line height;
- change image crop;
- use a mobile media alternative where required.

The editor shows warnings for:

- clipped content;
- horizontal overflow;
- text below minimum readable size;
- tap targets below minimum size;
- overlapping interactive elements;
- hidden required form fields;
- inaccessible reading order.

## 5.13 Accessibility controls

- alt text;
- decorative image/shape;
- semantic heading level;
- landmark role for approved layout blocks;
- link/button accessible name;
- screen-reader-only text;
- caption/transcript;
- DOM order for layered sections;
- focus order preview;
- contrast meter;
- reduced-motion preview;
- skip-link target selection.

The client cannot manually set arbitrary ARIA attributes that could create invalid accessibility semantics.

## 5.14 Data-binding controls

Dynamic blocks can bind to:

- current Nursery;
- all published Nurseries;
- current Event;
- upcoming/past Events;
- current Blog article;
- Blog archive;
- current Job;
- open Jobs;
- Testimonials;
- fee PDFs;
- Food Hygiene values;
- global phone/email/hours;
- social links.

The Data panel controls:

- source;
- filter;
- order;
- item limit;
- pagination;
- selected/manual items;
- empty state;
- field visibility;
- label patterns.

Dynamic content is styled visually but edited at its source record.

## 6. Approved element and block library

## 6.1 Primitive blocks

- Section.
- Container.
- Row.
- Stack.
- Grid.
- Columns.
- Layered Canvas.
- Group.
- Spacer.
- Divider.
- Shape.
- Heading.
- Rich Text.
- List.
- Quote.
- Eyebrow/Badge.
- Icon.
- Image.
- Video.
- Button.
- Button Group.
- Social Links.
- File/PDF Link.
- Breadcrumb.

## 6.2 Composite content blocks

- Hero.
- Split Image/Text.
- Feature Cards.
- Benefit Row.
- Icon Grid.
- Photo Grid.
- Gallery/Lightbox.
- Timeline.
- Quote Band.
- Trust/Accreditation Row.
- CTA Band.
- Contact Details.
- Staff Cards.
- FAQ/Accordion.
- Tabs.
- Logo Row.
- Stats/Milestones.
- Pricing/Download Cards.
- Empty State.
- Notification/Notice.

## 6.3 Dynamic collection blocks

- Nursery Directory.
- Nursery Hero/Data.
- Nursery Gallery.
- Nursery Contact Details.
- Nursery Ofsted Information.
- Nursery Hygiene Information.
- Nursery Fee Downloads.
- Testimonial Feed.
- Blog Featured Article.
- Blog Archive.
- Related Articles.
- Event Archive.
- Event Details.
- Vacancy Archive.
- Vacancy Details.
- Dynamic branch selector.

## 6.4 Secure form blocks

- Contact Form.
- Book-a-Visit Form/Modal.
- Availability Form.
- Career Application Form.

Visual controls cover layout, labels, helper text, button styles and success/error presentation.

Form-builder controls may add approved custom fields:

- text;
- email;
- phone;
- textarea;
- select;
- radio;
- checkbox;
- date;
- time;
- heading;
- explanatory text;
- consent;
- file only in approved Career contexts.

Core routing/security fields cannot be removed from a publishable form. The editor may remove them in a draft, but readiness must block publication until the required operational contract is restored.

Custom submitted fields require:

- immutable generated field key;
- versioned field schema;
- structured extra-data storage;
- Submissions display;
- CSV export;
- notification rendering;
- sanitisation and validation;
- retention handling.

## 7. Global visual templates

## 7.1 Brand Styles

Editable:

- primary/secondary/neutral palettes;
- custom saved colors;
- gradients;
- heading/body fonts;
- font uploads in WOFF2 by authorised users;
- font licensing confirmation;
- type scale;
- spacing scale;
- border-radius presets;
- shadow presets;
- default button styles;
- link styles;
- container widths;
- motion presets.

Changing a global token shows a preview of every affected component before publishing.

## 7.2 Header

The Header is a visual template.

Client can:

- add/remove/reorder logo, navigation, CTA, icons, social links and shapes;
- change logo;
- change background;
- change height and spacing;
- edit menu typography and states;
- add decorative elements;
- create sticky/non-sticky variants;
- set desktop and tablet layout;
- edit dropdown shape and styling.

Readiness checks:

- mobile-menu access remains available;
- logo/home link has an accessible name;
- menu controls are keyboard accessible;
- fixed/sticky header does not obscure content.

## 7.3 Navigation and Parent Information menu

- add/delete/duplicate/reorder items;
- change labels;
- choose internal page, dynamic Nursery list, external URL or anchor;
- create dropdown groups;
- change item/button shape;
- change default/hover/active styles;
- add icons;
- edit dropdown columns;
- edit menu background/border/shadow;
- hide by breakpoint.

Underlying pages are not deleted when a navigation tab is deleted.

## 7.4 Mobile menu

- edit mobile-only layout;
- add/remove/reorder items;
- submenu/accordion behavior;
- logo;
- close/back/view-more labels;
- menu background and shapes;
- typography;
- spacing;
- social links;
- CTA.

Keyboard, focus trap, Escape and body-scroll behavior stay code-controlled.

## 7.5 Footer

- add/delete/reorder columns and elements;
- replace logo;
- edit text;
- dynamic Nursery contact block;
- dynamic Ofsted block;
- navigation links;
- social links;
- shapes/backgrounds;
- copyright;
- legal links;
- expand/collapse behavior;
- desktop/tablet/mobile layout.

## 7.6 Cookie UI

The visual shell can be styled and its approved text edited.

Locked:

- consent categories;
- storage version;
- consent logic;
- analytics gating;
- essential-cookie behavior.

Legal copy publication requires the designated capability.

## 8. Page-by-page visual builder map

Every page uses the full universal controls. The lists below define the initial editable block template, not a permanent limitation. The client may add, duplicate, move or delete approved blocks.

## 8.1 Home `/`

Initial blocks:

- Hero Video/Layered Canvas.
- Feature-link card row.
- Benefit/icon row.
- About split section.
- Milestone badges.
- Testimonial feed over image.
- Trust/accreditation row.
- optional CTA.

Special controls:

- video and poster;
- layered shapes/overlays;
- featured testimonial selection;
- dynamic internal links;
- hexagon/circle/card image masks;
- section order and visibility.

## 8.2 Our Nurseries `/nurseries`

Initial blocks:

- Page Header.
- Rich introductory text.
- Dynamic Nursery Directory.
- empty state.
- CTA.

Special controls:

- card layout/list/grid/carousel;
- columns per breakpoint;
- dynamic field visibility;
- card image shape;
- card hover state;
- order from Nursery records or manual selection.

## 8.3 Nursery detail `/nurseries/:slug`

Initial blocks:

- dynamic Nursery Hero;
- welcome split section;
- philosophy band;
- gallery;
- feature grid;
- meals section;
- team cards;
- parent-partnership cards;
- branch contact details;
- accreditation row;
- testimonial feed;
- booking modal trigger.

Everything can be moved, duplicated, hidden or deleted.

Bindings such as branch name, address, phone and email remain linked to the current Nursery unless the client deliberately converts a binding into static local text.

Support:

- global Nursery template;
- per-Nursery design override;
- reset override to template;
- preview Hounslow/Heston/Hammersmith/future branch from a selector.

## 8.4 About `/about`

Initial blocks:

- Page Header;
- story split section;
- approach cards;
- quote;
- staff/training section;
- typical-day timeline;
- snack notice;
- CTA.

## 8.5 Curriculum `/curriculum`

Initial blocks:

- Our Philosophy;
- The EYFS;
- The Montessori Approach;
- Where They Meet;
- optional CTA.

Each section can change layout, image shape, order, background and rich text.

## 8.6 Careers `/careers`

Initial blocks:

- Page Header;
- vacancies button;
- intro;
- application form;
- Why Work With Us cards;
- gallery;
- What We Are Looking For cards;
- closing CTA.

## 8.7 Vacancies `/careers/vacancies`

Initial blocks:

- Page Header;
- filter controls;
- dynamic Vacancy Archive;
- empty state;
- general application CTA.

The client styles filter tabs/chips and job cards, but job data remains in Jobs.

## 8.8 Vacancy detail `/careers/vacancies/:slug`

Initial blocks:

- dynamic header;
- metadata chips;
- rich description;
- benefits;
- apply card/form;
- back link;
- missing/closed state.

## 8.9 Apply `/careers/apply`

Initial blocks:

- Page Header;
- Career Application Form;
- privacy/support copy;
- success state.

## 8.10 Events `/events`

Initial blocks:

- Page Header;
- dynamic Upcoming Events;
- dynamic Past Events;
- empty state;
- CTA.

Event card image mask, layout, buttons, dates and filters are visually styleable.

## 8.11 Event detail `/events/:slug`

Initial blocks:

- dynamic Page Header;
- event image;
- event metadata;
- rich event body;
- booking information;
- Book/Contact action;
- Back action;
- missing-event state.

## 8.12 Contact `/contact`

Initial blocks:

- Page Header;
- dynamic branch contact cards;
- social links;
- form introduction;
- Contact Form;
- optional CTA/map/directions.

## 8.13 Branch Contact `/contact/:slug`

Initial blocks:

- dynamic Page Header;
- Contact Form;
- branch image;
- dynamic contact details;
- gallery/location card;
- Other Nurseries links.

## 8.14 Check Availability `/check-availability`

Initial blocks:

- Page Header;
- image/intro panel;
- highlight cards;
- Availability Form;
- success state;
- dynamic branch cards.

## 8.15 Fees `/fees`

Initial blocks:

- Page Header;
- feature image;
- dynamic Fee Download cards;
- Funding Estimate CTA.

## 8.16 Fee Calculator `/fee-calculator`

Initial blocks:

- Page Header;
- calculator inputs;
- result cards;
- disclaimer;
- branch fee-download action.

Visual layout and wording are editable. Calculation rules are edited through a validated calculator configuration panel, not rich text.

## 8.17 Funded Childcare `/funded-childcare`

Initial blocks:

- Page Header;
- funding cards;
- application steps;
- benefits;
- fee downloads;
- resource links;
- FAQs;
- CTA.

## 8.18 Blog `/blogs`

Initial blocks:

- Page Header;
- filter controls;
- featured article;
- archive grid;
- pagination;
- loading/error/empty states;
- CTA.

The article collection is styled through dynamic blocks.

## 8.19 Blog detail `/blogs/:slug`

Initial blocks:

- article header;
- category/date;
- featured image;
- article body;
- related articles;
- CTA;
- loading/error/deleted states.

Article-body authors can use approved editorial blocks and rich-text formatting. Article page-shell design remains in the global Blog Detail template.

## 8.20 Food Hygiene `/food-hygiene-rating`

Initial blocks:

- Page Header;
- dynamic Food Hygiene cards;
- public-record actions;
- pending state.

## 8.21 Testimonials `/testimonials`

Initial blocks:

- Page Header;
- dynamic Testimonial Feed;
- pagination;
- loading/error/empty states;
- CTA.

## 8.22 Privacy `/privacy`

Initial blocks:

- Page Header;
- rich legal sections;
- lists/links;
- cookie-settings action;
- review metadata.

The layout is visually editable. Publishing legal copy remains permission-controlled.

## 8.23 Not Found and system states

Initial blocks:

- shape/illustration;
- heading;
- rich text;
- Home button;
- Contact button.

System templates also cover:

- loading;
- offline;
- general error;
- unavailable collection;
- maintenance notice.

## 9. Implementation phases

## Phase 0 — Baseline and editor proof of concept

Implementation status — 27 July 2026: the Local-only engineering proof of
concept is complete. It now uses the real React entry point for its protected
preview and does not replace an existing public route. Automated functional,
security, persistence, revision and build checks pass. The remaining exit-gate
item is client visual/interaction approval in a normal browser. See
`PHASE-0-VISUAL-BUILDER-EVIDENCE.md`.

Goal: prove the chosen editor can deliver exact React visual output before building all controls.

Work:

- fresh production and Local baseline;
- verified rollback backup;
- current route screenshots and data-contract inventory;
- create disposable builder plugin prototype;
- implement Section, Heading, Rich Text, Image, Button and Shape;
- render the same prototype block tree in admin and public React;
- test inline text, bold/italic/underline/list, drag reorder, resize, responsive values and undo;
- create one layered hero prototype;
- measure admin performance and saved payload.

Prototype page:

- a Local-only copy of one Home section;
- no existing public route switched;
- no production deploy.

Exit gate:

- editor and public render are visually equivalent;
- saved/reloaded block data is stable;
- revisions restore content and styles;
- user approves the interaction model.

## Phase 1 — Builder core and persistence

- full-screen admin shell;
- page selector;
- top toolbar;
- left Add/Layers/Media/Templates panels;
- right properties panel;
- selection and multi-selection;
- drag/drop;
- add/duplicate/delete;
- grouping;
- layer rename/order/visibility/lock;
- autosave;
- undo/redo;
- revisions;
- draft preview;
- publishing workflow;
- template records;
- schema versioning;
- feature flags;
- public AST endpoint;
- permissions;
- audit events.

Tests:

- reload stability;
- large block tree;
- concurrent edit lock;
- unauthorised draft access;
- revision restore;
- unknown block;
- malformed attributes;
- editor crash recovery.

## Phase 2 — Universal text, media and shape controls

- complete RichText toolbar;
- custom underline format;
- headings/lists/quotes;
- typography panel;
- color/gradient panel;
- image editor;
- video editor;
- shape library;
- border/radius/shadow;
- opacity/filter/overlay;
- buttons and links;
- icons and approved SVG pipeline;
- responsive overrides.

Tests:

- formatting combinations;
- paste from Word/Google Docs;
- sanitisation;
- font-loading failure;
- image/video edge cases;
- all shape variants;
- contrast warnings.

## Phase 3 — Layout, layered canvas and responsive engine

- containers;
- flex;
- grid;
- columns;
- stacks;
- spacing;
- sizing;
- freeform layered section;
- alignment/snap guides;
- resize handles;
- z-order;
- breakpoint inheritance;
- overflow detection;
- responsive warnings;
- device preview;
- accessible DOM order.

Tests:

- nested layouts;
- all supported units;
- overlap and clipping;
- 360–1440 px;
- 200% zoom;
- keyboard movement;
- layered visual vs reading order.

## Phase 4 — Reusable composites and interactions

- cards;
- icon grids;
- galleries/lightbox;
- tabs;
- accordion/FAQ;
- timelines;
- CTA;
- quote bands;
- logo rows;
- trust rows;
- carousels;
- modal triggers;
- animations;
- reusable/synced sections;
- save as template;
- detach/reset synced design.

Tests:

- keyboard tabs/accordion/carousel;
- focus management;
- empty/large repeaters;
- synced update propagation;
- reduced motion.

## Phase 5 — Global design system and global templates

- Brand Styles;
- header;
- desktop navigation;
- dropdowns;
- parent-information menu;
- mobile menu;
- footer;
- social sidebar;
- default CTA;
- Cookie UI shell;
- global focus/link/button states.

Tests:

- every navigation state;
- no-nav and many-item states;
- dropdown keyboard use;
- mobile focus trap;
- sticky header;
- logo deletion/replacement;
- long labels;
- global token preview and rollback.

## Phase 6 — Dynamic collection blocks

- Nursery blocks;
- Testimonial feed;
- Blog blocks;
- Event blocks;
- Job blocks;
- fee downloads;
- hygiene cards;
- data-binding panel;
- filters/order/selection/pagination;
- loading/error/empty states;
- editor fixtures.

Tests:

- zero/one/many records;
- draft/incomplete exclusion;
- arbitrary future Nursery/Event/Job;
- pagination;
- REST failure;
- data binding converted to static/local content;
- no duplicate sources.

## Phase 7 — Visual form builder integration

- Contact;
- Book a Visit;
- Availability;
- Career Apply;
- form styling;
- add/reorder/delete approved fields;
- protected operational core fields;
- custom field schema and storage;
- Submissions display;
- email/digest rendering;
- export;
- validation;
- consent and retention.

Tests:

- existing workflows unchanged;
- custom fields end to end;
- draft missing required routing field;
- invalid upload;
- spam/rate limits;
- notification routing;
- private CV download;
- accessibility.

## Phase 8 — Home

- migrate current Home into blocks;
- implement every Home-specific control;
- preserve current visual output as initial revision;
- test hero/video/layered shapes/testimonials/trust;
- Local sign-off.

## Phase 9 — Nursery family

- Our Nurseries index;
- global Nursery detail template;
- Hounslow override;
- Heston override;
- Hammersmith override;
- future Nursery preview;
- branch Contact template;
- booking integration;
- Local sign-off per branch.

## Phase 10 — About and Curriculum

- About page;
- Curriculum page;
- timelines;
- approach cards;
- images/shapes;
- rich policy/educational copy;
- Local sign-off per route.

## Phase 11 — Careers family

- Careers;
- Vacancies;
- Vacancy detail;
- Apply;
- gallery;
- dynamic job blocks;
- form integration;
- Local sign-off.

## Phase 12 — Events family

- Events;
- Event detail;
- filters/states;
- event-to-contact prefill;
- date/timezone checks;
- Local sign-off.

## Phase 13 — Contact and Availability

- Contact;
- branch Contact;
- Check Availability;
- branch cards;
- secure forms;
- responsive form layout;
- Local sign-off.

## Phase 14 — Parent-information pages

Subphases completed one at a time:

1. Fees.
2. Fee Calculator.
3. Funded Childcare.
4. Blog archive.
5. Blog detail.
6. Food Hygiene.

Each receives individual migration, regression and Local approval.

## Phase 15 — Testimonials, Privacy, Cookie and system pages

- Testimonials;
- Privacy;
- Cookie UI;
- 404;
- loading/error/offline states;
- legal permissions;
- consent regression;
- Local sign-off.

## Phase 16 — Migration, parity and removal of legacy content paths

- convert all current JSX sections into initial block trees;
- migrate current images, positions and alt text;
- migrate `am_about`;
- preserve collection records;
- preserve Site Settings;
- preserve submissions;
- diff visual-builder output against reference screenshots;
- keep route-level rollback flags;
- remove a hardcoded source only after its route is approved;
- document every transform and compatibility bridge.

No full Local database import to production.

## Phase 17 — Complete QA and UAT

Automated:

- React lint/build;
- WordPress build;
- PHP syntax;
- block-schema validation;
- parse/serialize round-trip;
- sanitisation;
- REST permissions;
- revision restore;
- migration idempotence;
- collections;
- forms/submissions;
- direct-route metadata;
- broken links/media.

Responsive:

- 360;
- 390;
- 768;
- 1024;
- 1440;
- 200% zoom;
- custom narrow widths.

Browsers:

- Chromium;
- Firefox;
- WebKit;
- real phone smoke test.

Accessibility:

- keyboard-only editor and public site;
- screen-reader smoke test;
- headings;
- landmarks;
- alt text;
- form labels/errors;
- focus;
- contrast;
- reduced motion;
- layered-section reading order.

Performance:

- editor start time;
- drag/typing responsiveness;
- large-page performance;
- public JS/CSS change;
- page payload size;
- responsive media;
- Core Web Vitals;
- no memory leak during extended editing.

Security:

- XSS attempts in rich text, links, SVG, colors and block attributes;
- no arbitrary scripts/CSS;
- draft preview isolation;
- capability bypass;
- malformed imported design JSON;
- unsafe external embeds;
- secure form contract unchanged.

UAT tasks:

1. Edit and format text.
2. Create bullets and numbered lists.
3. Change responsive font sizes.
4. Add and delete an element.
5. Duplicate/reorder/delete a section.
6. Add and edit a shape.
7. Create a layered hero.
8. Replace/crop/position an image.
9. Change a button’s states.
10. Add/edit/reorder tabs.
11. Edit Header and Footer.
12. Hide an element only on mobile.
13. Add a reusable section to another page.
14. Preview a draft.
15. Publish.
16. Restore a previous revision.
17. Edit a dynamic collection block without duplicating its records.
18. Add an approved form field and verify it in Submissions.

## Phase 18 — Staged production rollout

No production work without explicit approval.

Wave 1:

- builder/plugin code dormant;
- production permissions and contract checks;
- no public route switches.

Wave 2:

- seed global templates and page drafts;
- production parity comparison;
- no public route switches.

Wave 3:

- Global Header/Footer and Home;
- approval hold.

Wave 4:

- Nursery family;
- approval hold.

Wave 5:

- About, Curriculum and Careers;
- approval hold.

Wave 6:

- Events, Contact and Availability;
- approval hold.

Wave 7:

- parent-information, Blog, Testimonials, legal/system pages;
- final crawl and UAT.

Every wave:

- fresh verified backup;
- checksum manifest;
- schema/contract check;
- route smoke tests;
- forms/submissions regression;
- activation flags;
- rapid route-level legacy rollback;
- recorded approval.

## 10. Publish-readiness rules

The user can delete or change visible elements freely in a draft. Publishing may show warnings or block only when the result would break a critical contract.

Blocking examples:

- invalid/unsafe block data;
- an active form missing required email/nursery/routing fields;
- inaccessible draft-preview token;
- unsupported custom script/embed;
- missing required legal consent block in a form;
- corrupted global template;
- header interactive control without an accessible label.

Warning examples:

- no H1;
- low contrast;
- very small text;
- missing alt text;
- content outside mobile canvas;
- oversized media;
- no navigation items;
- blank page;
- deleted CTA;
- hidden section;
- heading-level skip;
- external link failure.

Warnings are visible but do not unnecessarily prevent a deliberate design decision by an authorised Publisher.

## 11. Recovery and safety

- autosave;
- undo/redo;
- WordPress revisions;
- named checkpoints;
- revision diff;
- Restore button;
- trash for reusable sections;
- route-level legacy renderer flag;
- global-template emergency reset;
- exportable design JSON;
- schema migrations;
- unknown-block preservation;
- pre-deploy backup;
- production rollback.

Delete behavior:

- Delete element/section removes it from the current draft and is undoable.
- Publishing records the deletion in a revision.
- Restoring an old revision recovers it.
- Deleting a navigation item does not delete its page.
- Deleting a dynamic block does not delete collection records.
- Deleting a form block does not delete submissions.
- Deleting a global template’s contents does not delete the system template record.

## 12. Performance boundaries

Full flexibility still needs bounded technical values.

- Numeric sizes have safe minimum/maximum ranges.
- The editor warns before using extremely large images/videos.
- Custom fonts are locally hosted and subset where practical.
- Custom colors are allowed; contrast is checked.
- Arbitrary CSS and JavaScript are not allowed.
- Freeform layers are capped per section to protect mobile/admin performance.
- Excessively nested layouts are warned against.
- Dynamic archives use pagination.
- Initial page payload excludes unrelated page trees.
- Responsive image variants are generated.

These boundaries protect the website engine; they do not remove the requested visual editing controls.

## 13. Documentation and handover

Deliver:

- visual editor walkthrough;
- text-formatting guide;
- shapes and layers guide;
- desktop/tablet/mobile guide;
- header/menu/footer guide;
- reusable sections guide;
- dynamic-data guide;
- forms guide;
- image/video sizing guide;
- accessibility checklist;
- draft/review/publish guide;
- revision/restore guide;
- emergency reset guide;
- route ownership map.

## 14. Recommended immediate next step

Phase 0 and Phase 1 are complete on Local. Begin Phase 2A: the universal
rich-text and typography engine.

Use `PHASE-1-VISUAL-BUILDER-DELIVERY.md` as the completed baseline and
`PHASE-2-VISUAL-CONTROLS-HANDOFF.md` as the implementation contract.

Do not start real route migration before the Phase 2 primitive controls and
Phase 3 responsive layout engine pass their Local acceptance gates.
