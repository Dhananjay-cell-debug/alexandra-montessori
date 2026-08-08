# Alexandra Montessori — Full-Site WordPress CMS Implementation Plan

> **Superseded on 27 July 2026.** The client-editing scope was expanded from
> structured content controls to a Canva-style visual theme builder with
> add/delete/duplicate/reorder, rich-text formatting, shapes, responsive design
> controls and editable layouts. The authoritative plan is now
> `FULL-SITE-VISUAL-THEME-BUILDER-PLAN.md`. Keep this document only as the
> earlier structured-CMS baseline and ownership inventory.

Prepared: 27 July 2026  
Scope: preparation and phased implementation plan only; no production change is authorised by this document.

## 1. Outcome

Turn the current WordPress admin into a controlled full-site content system for the React website.

The client should be able to edit:

- page headings, paragraphs, labels, buttons and links;
- page, section, card, gallery, hero, poster and background images;
- image alternative text and focal position/crop;
- the logo and other approved brand assets;
- repeated cards, FAQs, timeline rows, resources and gallery items;
- the visibility and approved order of page sections;
- per-page SEO titles, descriptions and social sharing images;
- the existing collections: Nurseries, Blog, Events, Testimonials and Jobs;
- global content used in the header, parent-information menu, footer, social bar and shared calls to action.

The client should not be able to accidentally edit:

- React/PHP code, CSS classes, JavaScript or arbitrary scripts;
- public route slugs or the relationship between forms and their secure endpoints;
- hidden submission routing, notification recipients, private CV storage or security controls;
- raw HTML that could break the layout;
- unrestricted fonts, spacing or page-builder blocks unless a later design-control phase is explicitly approved.

This is “content-only theme editing”: broad content flexibility inside tested Alexandra Montessori components.

## 2. Current baseline

The audit covered the React source, the active Local WordPress theme, the current public domain and the existing WordPress data contract.

### Already CMS-authoritative

- Nurseries: directory cards, detail routes, contact/routing choices and branch facts.
- Blog: article records, featured placement, archive, filters and detail routes.
- Events: archive and detail routes.
- Testimonials: homepage subset and paginated archive.
- Jobs: vacancies, detail routes and application choices.
- About-us homepage section: heading, body, image and milestones.
- Site Settings: public phone, email, hours, address, social links and analytics/search settings.
- Media Library collections.
- Submissions and secure form workflows.

### Still largely hardcoded in React

- most Home sections apart from the About content and testimonial records;
- the Our Nurseries introduction and card microcopy;
- most nursery-detail template sections;
- the About page;
- the complete Curriculum page;
- the Careers landing page and gallery;
- the content surrounding vacancies and applications;
- page-level Events, Blog and Testimonials headings, empty states and calls to action;
- Contact and Check Availability page content;
- Fees, Fee Calculator, Funded Childcare and Food Hygiene page-shell content;
- header labels/order, parent-information menu labels/order, footer labels and logo;
- shared CTA copy, form labels/help/success messages, Cookie UI copy, Privacy and 404 content;
- most per-route SEO metadata.

### Current route inventory

There are 23 route patterns to account for:

1. `/`
2. `/nurseries`
3. `/nurseries/:slug`
4. `/about`
5. `/curriculum`
6. `/fees`
7. `/fee-calculator`
8. `/funded-childcare`
9. `/blogs`
10. `/blogs/:slug`
11. `/food-hygiene-rating`
12. `/events`
13. `/events/:slug`
14. `/testimonials`
15. `/careers`
16. `/careers/vacancies`
17. `/careers/vacancies/:slug`
18. `/careers/apply`
19. `/check-availability`
20. `/contact`
21. `/contact/:slug`
22. `/privacy`
23. the not-found route

The public domain currently serves the same WordPress/React bundle as the active Local project. The new work must begin from a fresh read-only production baseline because earlier handoff notes still reference the previous staging domain.

## 3. Architecture decision

### 3.1 Use a dedicated content plugin

Move new page-schema, admin-field, revision, preview and public-content contract code into a dedicated must-use plugin, separate from:

- the React-rendering theme; and
- the existing Alexandra Operations plugin used for submissions, notifications and private files.

Proposed responsibility split:

- `alexandra-site-content`: page/global content types, fields, validation, preview, public REST payloads and migration tools;
- `alexandra-theme`: React assets, server shell and compatibility bridge;
- `alexandra-operations`: forms, submissions, mail, private files and operational workflows.

Content types should not disappear if the rendering theme is ever replaced.

### 3.2 Add locked singleton “Website Pages”

Create a non-public `am_site_page` post type. Seed exactly one protected record for each editable page/template.

Each record has:

- an immutable template key;
- a fixed public route;
- grouped fields matching the visible sections on that route;
- draft/review/published state;
- autosave and revisions;
- registered, revision-enabled post meta;
- a Preview button that opens the exact public route with authorised draft data;
- a website-result/readiness status;
- no client-facing Add New, Trash or slug-changing action.

Dynamic detail templates use one shell record plus their collection record:

- Nursery detail shell + the selected Nursery.
- Blog detail shell + the selected Blog article.
- Event detail shell + the selected Event.
- Vacancy detail shell + the selected Job.
- Contact-location shell + the selected Nursery.

This prevents duplicate fields and conflicting sources.

### 3.3 Add revisioned “Global Components”

Create protected singleton records for:

- Brand & logos.
- Header & navigation.
- Footer.
- Shared calls to action.
- Social presentation.
- Form and status copy.
- Cookie and legal presentation copy.
- SEO defaults.

Operational recipients, SMTP, monitoring, retention and private-file configuration stay outside these content records.

### 3.4 Keep existing collections

Do not rebuild working collections.

- `am_nursery` remains the source for branch facts and routes.
- WordPress Posts remain the source for Blog articles.
- `am_event` remains the source for Events.
- `am_testimonial` remains the source for Testimonials.
- `am_job` remains the source for vacancies.
- WordPress attachments remain the source for public media.

Only extend a collection when its visible template needs a genuinely record-specific field.

### 3.5 Version the frontend contract

Introduce a new versioned contract without breaking schema version 4 during rollout.

Proposed public shape:

```text
window.amData
├── schemaVersion
├── globals
│   ├── brand
│   ├── navigation
│   ├── footer
│   ├── sharedCtas
│   ├── forms
│   └── seo
├── pages
│   ├── home
│   ├── nurseriesIndex
│   ├── nurseryDetail
│   ├── about
│   ├── curriculum
│   └── ...
├── nurseries
├── events
├── testimonials
├── jobs
└── archive endpoints
```

Implementation rules:

- React uses a typed `cmsPage(templateKey, previewDefaults)` helper.
- WordPress is authoritative whenever the new schema is present.
- Static React defaults remain only for standalone Vite/design preview.
- In WordPress mode, an intentionally blank optional field hides its element; it must not silently revive stale hardcoded copy.
- Required missing content produces an exact admin readiness warning and a safe public fallback state.
- Page and global payloads are sanitised and size-bounded.
- Large archives and article bodies continue to use REST endpoints rather than expanding the initial HTML indefinitely.

### 3.6 Store media as structured assets

Each editable image should store an attachment ID plus presentation metadata:

- public URL and responsive variants generated from the attachment;
- alt text;
- focal X/Y position;
- optional caption;
- decorative/non-decorative flag;
- expected aspect ratio and recommended minimum dimensions.

Logo fields use separately approved desktop/header/footer/favicon/social variants where needed. Replacing one logo must not distort every context.

Video fields should support:

- MP4/WebM attachment;
- poster image;
- mobile fallback/poster;
- mute/autoplay/loop controls only where the component safely supports them;
- upload type and size validation.

### 3.7 Content workflow

Recommended workflow:

1. Editor saves a Draft.
2. Editor previews the exact React route.
3. Reviewer checks the page readiness panel and visual preview.
4. Publisher publishes.
5. WordPress records the revision, author and timestamp.
6. A previous published revision can be restored.

The existing access roles should receive only the page/global capabilities appropriate to them.

## 4. Admin experience

The Website Content dashboard should become a real site map.

### Dashboard groups

- Global Components
- Main Navigation Pages
- Nursery Pages
- Parent Information Pages
- Collections
- Legal & System Pages
- Media

Each tile should show:

- route;
- Published/Draft/Needs attention state;
- last updated time and user;
- Edit button;
- Preview button;
- exact website result.

### Edit-screen structure

Every page uses the same admin conventions:

- sections appear in the same order as the website;
- field groups are titled with the visible section name;
- helper thumbnail or route anchor identifies where the field appears;
- character guidance is shown for constrained headings/buttons;
- link fields distinguish internal pages, dynamic records, documents and external URLs;
- image fields show required ratio, minimum size, alt text and focal controls;
- repeaters have explicit minimum/maximum items;
- optional sections have a Show section toggle;
- approved sections can be reordered, but individual fields cannot be dragged into another component;
- destructive Remove actions require confirmation;
- required-field errors name the exact missing field;
- the Preview button remains visible while editing.

## 5. Standard definition of done for every page phase

A page phase is not complete when fields merely appear in wp-admin. It is complete only when all eight layers agree:

1. **Inventory:** every visible text, image, link and state has an owner.
2. **Schema:** field type, constraints, sanitisation, optional/required behavior and defaults are documented.
3. **Admin:** fields are understandable and permissions are correct.
4. **Contract:** PHP returns the intended public shape with no private data.
5. **React:** the page uses CMS data without layout or routing regressions.
6. **Migration:** current approved content is seeded exactly and idempotently.
7. **Verification:** automated, responsive, accessibility and failure-state tests pass.
8. **Sign-off:** Local preview is approved before that page is eligible for production.

For each phase, record evidence in a phase checklist with before/after screenshots and command results.

## 6. Phased implementation

## Phase 0 — Baseline, ownership map and recoverability

Goal: freeze a trustworthy starting point before changing schemas.

Work:

- re-audit public production, Local WordPress and source timestamps;
- record active theme bundle, plugin versions, WordPress version and data-contract shape;
- export a read-only inventory of every current page section and asset;
- snapshot the production database, active theme, MU plugins and relevant uploads;
- verify backup checksums and a rollback dry-run procedure;
- create a content-ownership map: global, page shell, collection record, operational setting or code-locked;
- record all hardcoded content keys before removing any;
- capture reference screenshots at 390, 768, 1024 and 1440 px;
- capture the current HTML title/meta behavior for every direct route.

Important finding to include: direct React routes currently return the SPA with HTTP 200, but the server-generated WordPress HTML title can still say “Page not found” before React hydrates. The foundation must correct route-aware server metadata.

Exit gate:

- exact backups exist and are verified;
- all 23 route patterns have an inventory owner;
- no production content has changed.

## Phase 1 — CMS foundation, revisions, preview and contract

Goal: build the reusable machinery once before page-specific fields multiply.

Work:

- scaffold the dedicated site-content MU plugin;
- register `am_site_page` and revisioned Global Component singletons;
- register immutable template keys and routes;
- register all meta with types, sanitisation, authorisation and revision support;
- prevent client creation, trashing, deletion and slug changes of singleton records;
- add generic section, repeater, link, media, alt-text and focal-point field helpers;
- implement draft preview with a short-lived, user-authorised preview context;
- implement readiness validators and exact “Website result” messages;
- add content contract versioning and compatibility with schema version 4;
- add `cmsPage`, `cmsGlobal`, media-normalisation and safe-rich-text helpers in React;
- add route-aware server title, description, canonical and social metadata;
- add a feature flag/page activation switch so completed pages can be enabled independently;
- add idempotent WP-CLI seeding and contract verification commands;
- add audit logging for page/global publishes and restores.

Verification:

- PHP syntax and WordPress coding checks;
- create/read/update/revision/restore tests;
- preview access tests for logged-out, viewer, editor and publisher roles;
- REST/private-field leakage tests;
- invalid URL, invalid media type, oversized repeater and missing-required-field tests;
- schema 4 fallback and new-schema tests;
- direct-load metadata tests for all routes;
- no regression to forms, Submissions, collections or client permissions.

Exit gate:

- a disposable test page can be drafted, previewed, published, revised and restored;
- unauthorised users cannot access draft content;
- existing public pages remain visually unchanged while page activation is off.

## Phase 2 — Global brand, header, menus, footer and shared elements

Goal: make all repeated public-site content editable once.

### Brand & logos

- site name, short name, tagline and motto;
- header badge logo;
- footer logo/lockup;
- browser favicon/site icon;
- default social sharing image;
- image alt/accessibility name;
- optional approved light/dark variants.

### Header and navigation

- main navigation item labels, order and visibility;
- internal route selection from a safe allow-list;
- Our Nurseries submenu remains generated from published Nursery records;
- Check Availability CTA label and link;
- parent-information menu title, item labels, order and visibility;
- mobile labels: View more, Back and accessible menu names.

Routes cannot be freely typed or deleted by the client.

### Footer

- tagline, email/hours presentation and section headings;
- View more/View less labels;
- Quick links order;
- Nursery Contacts and Ofsted rows remain collection-derived;
- footer legal link labels;
- copyright suffix;
- footer logo and approved accreditation mark;
- optional footer introduction.

### Shared components

- social sidebar visibility and label/color mapping;
- default CTA heading, body, button label and internal target;
- global empty-state/contact fallback copy;
- default SEO suffix and social image.

Migration:

- seed current logo, labels, menu order, CTA defaults and footer copy;
- retain the existing Site Settings contact facts and social URLs as source data;
- do not copy operational notification values into public global content.

Verification:

- desktop hover/dropdown, mobile menu, keyboard and screen-reader navigation;
- long and short label stress tests;
- zero/one/many Nursery submenu records;
- missing logo fallback;
- footer with long addresses/emails;
- internal links, focus order and no horizontal overflow.

Exit gate:

- the complete shell is editable without changing any existing route or operational behavior.

## Phase 3 — Home page

Goal: make every visible Home section editable.

Field groups:

1. SEO.
2. Hero video, WebM fallback, poster, mobile poster and accessible hidden heading.
3. Three feature links: heading, image, alt, focal point, CTA label, target and icon.
4. Benefits row: icon, text, order and visibility.
5. About-us section: existing heading/body/image/milestones/upcoming values migrated from `am_about`.
6. Testimonials section: section heading, background image/alt/focal point, selected featured testimonials or automatic first three, archive-button label.
7. Trust/accreditation row: icon, label, order and visibility.

Rules:

- feature targets use approved internal routes;
- testimonial records remain in the Testimonial collection;
- the homepage only selects/arranges records;
- video must always have a poster and a controlled size fallback;
- the design retains safe minimum/maximum card counts.

Verification:

- video autoplay fallback and reduced-motion behavior;
- missing/slow video;
- 0, 1, 2, 3 and more testimonials;
- long About copy and milestone counts;
- image crop at all reference widths;
- SEO and social image;
- visual parity after migration.

## Phase 4 — Our Nurseries index

Goal: make the whole directory page editable while Nursery records remain authoritative.

Field groups:

- SEO;
- page title;
- introductory paragraphs;
- dynamic-count alternative copy for one, many or zero nurseries;
- zero-state message and CTA;
- card CTA label and accessibility label pattern.

Nursery-derived values:

- branch name;
- card image and focal position;
- address;
- age range;
- hours;
- public route;
- display order.

Nursery records gain clearly labelled Directory Card fields where needed:

- optional separate card image;
- card image alt;
- card focal point;
- menu order.

Verification:

- zero, one, three and more than three published nurseries;
- incomplete/draft nursery exclusion;
- card order consistency in index, menus and forms;
- long branch names and addresses;
- all cards link to valid routes.

## Phase 5 — Nursery detail template and every current Nursery page

Goal: make every section on `/nurseries/:slug` editable for Hounslow, Heston, Hammersmith and future complete Nursery records.

### Per-Nursery fields

- SEO override and social image;
- hero image, alt and focal point;
- hero tagline, supporting line and CTA labels;
- area, address, postcode, phone, email, hours and age range;
- welcome eyebrow, title, body, image, alt and focal point;
- philosophy-strip icon and statement;
- gallery heading, images, alt text, focal points and order;
- What We Offer heading and repeatable icon/label items;
- Meals section show/hide, eyebrow, title, intro and repeatable image cards;
- Team section show/hide, heading and repeatable role/name/note/photo cards;
- parent-partnership section heading/intro and repeatable icon cards;
- contact-block labels;
- trust/accreditation selection;
- testimonial heading and selection mode;
- booking-button label.

### Shared defaults with branch overrides

Use a clear inheritance model:

1. Nursery detail global default.
2. Per-Nursery override.
3. Optional blank meaning “hide” only where the field explicitly allows hiding.

The admin must show whether a field is inherited or overridden and provide “Reset to shared default”.

### Safety

- Staff names/photos require a consent-confirmed checkbox and approved alt text.
- Branch email/phone changes are flagged because they affect public links and may affect routing.
- Ofsted, Food Hygiene and fee-PDF fields remain on the Nursery record as the single source.
- Future Nursery records receive generic safe defaults, never another branch’s photos or facts.

Verification:

- dedicated field-by-field checks for Hounslow, Heston and Hammersmith;
- arbitrary fourth-Nursery regression;
- per-branch meal visibility;
- 0–12 features, 0–12 gallery items and 0–6 team cards;
- testimonial matching/fallback behavior;
- booking modal preselects the correct branch;
- LocalBusiness structured data remains valid;
- contact details match menus, forms and footer;
- photo consent and alt-text readiness warnings.

Exit gate:

- all three current nursery pages pass independent visual sign-off;
- a disposable fourth nursery renders safely without code changes.

## Phase 6 — About page

Goal: make `/about` fully editable without confusing it with the Home About section.

Field groups:

- SEO and social image;
- page header title and introduction;
- story image, alt/focal point, eyebrow, title, body and curriculum CTA;
- approach-section heading and repeatable icon cards;
- featured quote and attribution;
- staff heading, introduction, body and training chips;
- typical-day heading and repeatable time/title/body rows;
- snack-time label and values;
- closing CTA.

The admin labels must distinguish:

- “Home — About us section”; and
- “About page”.

Verification:

- timeline order and time validation;
- 0/many training chips;
- quote punctuation and attribution;
- long staff copy;
- no duplicated source with Curriculum.

## Phase 7 — Curriculum page

Goal: make the complete four-part Curriculum page editable.

Field groups:

- SEO and social image;
- Our Philosophy: title, paragraphs, image, alt, focal point and shape treatment;
- The EYFS: title, paragraphs, image and media metadata;
- The Montessori Approach: title, paragraphs, image and layout side;
- Where They Meet: title, paragraphs, image and media metadata;
- section visibility and approved order.

Rules:

- the current four section types remain locked components;
- the client may reorder or hide approved sections;
- paragraphs are safe rich text with a restricted element allow-list;
- no arbitrary embeds/scripts.

Verification:

- paragraph length stress tests;
- image-left/image-right and circular-image variants;
- heading hierarchy after reordering/hiding;
- responsive crop and reading order;
- safe sanitisation of pasted Word/Google Docs content.

## Phase 8 — Careers, vacancies, vacancy detail and Apply

Goal: make all Careers presentation content editable while Jobs and secure applications remain authoritative.

### Careers landing

- SEO and page heading;
- vacancies CTA label and intro;
- application-form section heading/intro;
- Why Work With Us heading and repeatable icon cards;
- gallery heading, images, alt/focal point and View More label;
- What We Are Looking For heading and repeatable icon cards;
- closing CTA.

### Vacancies archive

- SEO, page heading and intro;
- filter labels and empty states;
- group labels;
- result-count wording;
- apply/general-interest CTA.

Job cards remain generated from open, complete Job records.

### Vacancy detail shell

- breadcrumb label;
- Benefits heading;
- Apply-panel heading and button labels;
- missing/closed-vacancy state;
- related/global CTA.

Title, description, salary, hours, location, benefits and application destination remain on each Job.

### Apply page and form presentation

- SEO and page header;
- form section headings, helper text, consent text and success message;
- field labels may be editable only through the restricted Forms Copy screen;
- field names, validation rules, upload limits and secure endpoints remain code-controlled.

Verification:

- no jobs, one job and many jobs;
- unknown future locations/types;
- open/closed/incomplete readiness;
- internal and external apply paths;
- direct job application preselection;
- file upload/privacy workflow unchanged;
- form labels remain correctly associated for accessibility.

## Phase 9 — Events archive and event detail

Goal: make page-shell content editable around the existing Event collection.

### Events archive

- SEO, title and intro;
- Upcoming/Past headings;
- card CTA label;
- Show More/Show Less labels;
- empty-state icon, heading, body and CTA;
- closing CTA.

### Event detail shell

- breadcrumb;
- date/time/location labels;
- booking-information heading;
- booking/contact button labels;
- back-to-events label;
- missing-event state;
- closing CTA.

Event-specific title, date, time, location, image, excerpt, body and booking information remain on the Event record.

Verification:

- no events, upcoming only, past only and mixed;
- boundary dates in the site timezone;
- missing optional time/image/booking information;
- event enquiry query parameter is preserved;
- safe event HTML;
- direct route metadata and social image.

## Phase 10 — Contact, branch Contact and Check Availability

Goal: make presentation content editable without weakening submission behavior.

### Contact page

- SEO, page title and intro;
- branch-card heading/label patterns;
- social-row visibility;
- form section heading and intro;
- contact-form labels, helper text and success/error copy through restricted Forms Copy.

Branch names, addresses, age ranges, phones and public emails remain Nursery data.

### Contact branch template

- header label patterns;
- form intro;
- Visit/Call/Email/Opening Hours labels;
- branch-image presentation;
- Other Nurseries heading and CTA pattern.

### Check Availability

- SEO, page header and intro;
- left-panel image, eyebrow, heading and body;
- highlight cards;
- form heading and introduction;
- form labels/help/consent/success/error copy;
- bottom branch-card section.

Locked operational behavior:

- endpoint;
- spam/rate-limit fields;
- submission keys;
- validation logic;
- branch-routing keys;
- notification behavior;
- consent record structure.

Verification:

- all three branches and arbitrary future branch;
- event-prefilled Contact message;
- direct branch contact routes;
- all form validation and failure states;
- duplicate submission protection;
- keyboard/mobile form completion;
- no email/phone routing drift.

## Phase 11 — Parent-information menu pages

### 11A. Fees

Editable:

- SEO, title and intro;
- page image, alt and focal point;
- card-heading pattern;
- download-button label;
- funding-estimate panel heading/body/button.

Derived:

- branch name, age range and fee PDF from each Nursery.

Tests:

- missing fee PDF;
- invalid/non-PDF attachment;
- one/many branches;
- annual PDF replacement with unchanged route.

### 11B. Fee Calculator

Editable:

- SEO, title and intro;
- field labels and explanatory copy;
- result-card labels;
- disclaimer;
- fee-sheet download label.

Configurable with strict numeric validation:

- standard hours per day;
- allowed attendance-day range;
- available funded-hour choices.

The first version remains an hours estimator, not a price quotation. If the client wants actual currency calculations, that requires a separate approved pricing/formula specification and legal disclaimer.

Tests:

- 0/15/30 funded hours;
- funded hours greater than attendance;
- branch without a fee PDF;
- numeric boundary and rounding behavior;
- accessibility of range/select controls.

### 11C. Funded Childcare

Editable:

- SEO, Page Header;
- funding-offer cards with images;
- application steps;
- benefits/features;
- fee-panel copy;
- external resource links and notes;
- FAQs;
- closing CTA.

Rules:

- external government/local-authority links display last-reviewed date;
- policy copy has a “review required” reminder;
- fee PDFs remain Nursery-derived;
- rich text is sanitised.

Tests:

- expired/broken external links;
- 0/many FAQs/resources;
- long policy copy;
- keyboard link behavior;
- content-review warning.

### 11D. Blog archive and article shell

Editable page shell:

- SEO defaults;
- archive header/intro;
- filter labels;
- Featured/Archive headings;
- loading, error and empty states;
- related-articles heading;
- back labels;
- closing CTAs.

Article title, category, date, image, excerpt and content remain in Posts.

Tests:

- pagination and year/month URLs;
- no articles;
- failed REST request;
- deleted/unpublished article;
- safe article HTML;
- related article count;
- article-level SEO/social metadata.

### 11E. Food Hygiene Rating

Editable:

- SEO, title and intro;
- rating-card label patterns;
- View Public Record label;
- pending/unavailable wording.

Derived:

- branch name/address;
- rating;
- inspection date;
- authority;
- public record URL.

Tests:

- numeric 0–5;
- pending/text status;
- missing URL/date/authority;
- public-link validation;
- branch count changes.

## Phase 12 — Testimonials, Privacy, Cookie UI, system pages and microcopy

### Testimonials archive

- SEO, page title and intro;
- loading/error/empty states;
- pagination labels;
- closing CTA.

Testimonial records remain in the collection.

### Privacy

- page title, effective date and approved section content;
- contact email;
- Cookie Preferences section;
- legal review metadata: reviewed by, reviewed date and next review date.

Only a designated Legal/Publisher capability should publish this page.

### Cookie UI

- banner summary;
- settings title/intro;
- category titles/descriptions;
- button labels.

The consent categories, storage key/version and consent behavior remain code-controlled. Legal wording changes require review.

### Not found and system states

- 404 heading/body/button;
- generic loading/error/offline messages;
- optional maintenance message;
- accessibility labels that are safe to expose as content.

### Form microcopy

Expose only:

- visible labels;
- placeholders;
- help text;
- consent wording;
- submit/loading/success/general-error messages.

Keep code-controlled:

- field keys;
- required/optional state;
- validation and sanitisation;
- API payload keys;
- security and rate-limit messages where disclosure is unsafe.

Verification:

- legal capability checks;
- stored consent migration;
- cookie keyboard/focus trapping;
- malformed rich text;
- 404 direct navigation;
- form label/control associations.

## Phase 13 — Section controls and advanced client flexibility

Goal: add flexibility after every fixed component is proven.

Approved controls:

- section Show/Hide;
- section order within an approved page-specific allow-list;
- repeatable-card order;
- internal link picker;
- safe icon picker;
- background tone selected from existing theme tokens;
- image focal position;
- per-page shared-CTA selection.

Not included by default:

- arbitrary page-builder blocks;
- raw CSS;
- custom JavaScript;
- free-form color values;
- arbitrary fonts;
- arbitrary column/grid configuration;
- client-created routes.

If true visual Gutenberg editing is later required, assess a separate content-only block/pattern phase. Do not mix that experiment into the structured CMS rollout.

Verification:

- every allowed section order;
- heading hierarchy;
- contrast for each allowed background tone;
- hide-all and single-section states;
- migration backward compatibility.

## Phase 14 — Full regression, client UAT and handover

Goal: prove the CMS as one coupled product.

### Automated

- React ESLint;
- root and WordPress builds;
- PHP syntax;
- contract/schema tests;
- seed idempotence;
- Local WordPress regression;
- REST route/status/permission tests;
- collection pagination/readiness tests;
- form and Submissions regression;
- broken-link and missing-media scan;
- structured-data validation;
- direct-route metadata checks.

### Visual and responsive

For every route/template:

- 360/390 px phone;
- 768 px tablet;
- 1024 px small desktop;
- 1440 px desktop;
- zoom at 200%;
- long-content and missing-content fixtures;
- no horizontal overflow, unexpected crop, layout shift or broken image.

### Accessibility

- keyboard-only navigation;
- visible focus;
- skip link;
- menu/dialog escape and focus behavior;
- logical headings;
- form labels/errors;
- image alternative text;
- decorative-image handling;
- reduced motion;
- color contrast;
- screen-reader smoke test.

### Browser and performance

- Chromium/Chrome;
- Firefox;
- WebKit/Safari-equivalent automated run;
- real mobile smoke test;
- image/video payload budgets;
- Core Web Vitals/Lighthouse comparison against baseline;
- no unnecessary all-page payload growth.

### Client UAT

The client must demonstrate:

1. edit a heading;
2. replace an image and set its focal point/alt;
3. add/reorder/remove a repeatable card;
4. hide and restore an optional section;
5. preview a draft;
6. publish and verify the public route;
7. restore a prior revision;
8. update a Nursery without breaking routing;
9. update a fee PDF;
10. create a Blog/Event/Testimonial/Job using the existing collection workflow.

### Handover

- concise editor guide with screenshots;
- field ownership map;
- media sizing/cropping guide;
- draft/review/publish guide;
- legal/policy review reminders;
- recovery and escalation guide;
- final signed route checklist.

## Phase 15 — Staged production rollout

No production step starts without explicit approval.

Recommended release waves:

### Wave 1 — Foundation, dormant

- deploy plugin/theme support with new page output disabled;
- verify production schema, permissions, REST and existing public parity;
- no public content source changes.

### Wave 2 — Seed and compare

- run host-locked, idempotent seed commands;
- migrate current React defaults and existing `am_about` values;
- do not import a complete Local database;
- export and compare each production page payload to approved Local content;
- leave existing collections and submissions untouched.

### Wave 3 — Global + Home

- enable Global Components and Home;
- verify shell, navigation, footer, analytics consent and Home at all breakpoints;
- hold for approval.

### Wave 4 — Nursery family

- enable Nurseries index, Nursery detail and branch Contact templates;
- verify every branch and routing;
- hold for approval.

### Wave 5 — Main navigation

- enable About, Curriculum, Careers, Events, Contact and Availability;
- verify forms and collection detail routes;
- hold for approval.

### Wave 6 — Parent information + legal/system

- enable Fees, Fee Calculator, Funded Childcare, Blog shell, Food Hygiene, Testimonials, Privacy, Cookie and system copy;
- perform final crawl and UAT.

Every wave must have:

- fresh pre-wave database/theme/plugin backup;
- checksum manifest;
- maintenance plan where needed;
- activation flag that can be rolled back without deleting new content;
- exact smoke-test checklist;
- recorded approval before the next wave.

## 7. Migration rules

- Preserve production as the source for public collection records and real submissions.
- Do not perform a whole-database Local-to-production import.
- Seed singleton page/global records by immutable template key.
- Make seed commands safe to run more than once.
- Never overwrite a non-empty client edit after initial migration.
- Convert current React hardcoded text/images into the initial published CMS revision.
- Migrate `am_about` into the Home page About group, then keep a compatibility read during one release.
- Keep `am_settings` for factual site settings and analytics; do not duplicate those values in page records.
- Keep branch facts, Ofsted, hygiene and fee PDFs in Nursery records.
- Record old value, new value, template key and timestamp for every migration.
- Remove hardcoded production fallbacks only after public parity and rollback are proven.

## 8. Verification matrix for each field type

| Field type | Required checks |
|---|---|
| Short text | empty, maximum length, HTML stripped, long word |
| Rich text | allowed tags, pasted document markup, links, script removal |
| Internal link | valid route, hidden page, dynamic record, keyboard behavior |
| External link | scheme allow-list, `noopener`, broken-link check |
| Email/phone | public formatting, usable link, routing-impact warning |
| Image | type, size, dimensions, alt, focal point, missing attachment |
| Logo | transparent background, aspect ratio, light/dark context |
| Video | type, poster, size, reduced motion, mobile fallback |
| PDF | MIME/signature, public URL, replacement, missing file |
| Repeater | zero/min/max, order, duplicate item, deletion |
| Toggle | on/off public semantics, no stale fallback |
| Section order | valid allow-list, heading order, responsive layout |
| SEO | title/description length, canonical, social image, direct load |
| Legal copy | permission, review date, revision and restore |

## 9. Risks and mitigations

### Too much client freedom breaks the design

Mitigation: component-specific fields, content-only controls, safe tokens, min/max counts, readiness validation and preview.

### Duplicate sources drift

Mitigation: one ownership map and derivation rules. Fee/Hygiene data stays with Nurseries; article/event/job/testimonial content stays with its collection.

### Inline data becomes too large

Mitigation: bootstrap only global/current-page essentials and use cached REST endpoints for archives/detail content where appropriate.

### Missing content revives old hardcoded text

Mitigation: WordPress-authoritative semantics in the new schema; optional blank means hide, required blank means Needs attention/safe state.

### Draft preview leaks

Mitigation: nonce/capability checks, short-lived preview context, `noindex`, no public cache and automated unauthorised-access tests.

### Image replacement harms layout/performance

Mitigation: attachment IDs, generated sizes, dimension guidance, focal controls, media validation and payload budgets.

### Legal/form wording changes create compliance risk

Mitigation: separate capability, review metadata, warnings and revisions; operational validation and consent behavior remain locked.

### A large release causes difficult rollback

Mitigation: page activation flags, Local approval per phase, staged production waves and non-destructive data migration.

## 10. Decisions required before implementation

The plan assumes the recommended answers below until confirmed.

1. **Design freedom:** content, media, approved section hide/show/order and safe theme tones are editable; arbitrary colors, fonts, grids, CSS and page-builder blocks are not.
2. **Publishing:** editors draft and preview; a publisher approves public changes.
3. **Legal/system copy:** Privacy, Cookie and consent wording is editable only by a restricted publisher/legal capability.
4. **Staff profiles:** role-only cards remain the default until the client supplies names/photos and confirms consent.
5. **Fee Calculator:** remains an hours/funding estimator, not a currency quote, until approved pricing rules exist.
6. **Navigation:** labels/order/visibility are editable, but route destinations come from an approved internal picker and core pages cannot be deleted.
7. **Languages:** this rollout is English-only. Multilingual requirements would change the schema and must be decided before field construction.
8. **Production cadence:** Local sign-off occurs per phase; production follows the six controlled release waves above.

## 11. Recommended execution order

Do not start several page phases simultaneously.

1. Complete Phase 0.
2. Complete and independently verify Phase 1.
3. Complete Phase 2 and Phase 3.
4. Complete Phase 4 and Phase 5 before any other page family.
5. Complete Phases 6–10 in route order.
6. Complete Phase 11 subphases one at a time.
7. Complete Phases 12 and 13.
8. Run Phase 14 across the complete system.
9. Begin Phase 15 only after explicit approval.

The first implementation task should be Phase 0 only: create the final ownership/field inventory and baseline evidence. It should not yet add page fields or deploy anything.
