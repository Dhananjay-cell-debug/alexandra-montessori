# Contact Page Identity and SEO Plan

## Implementation-grade parity contract

### Intent and feel

The search result and share card must feel as calm and direct as the page: a parent should understand that this is the single doorway to all three branches, not a generic corporate contact screen.

### Exact current React/public evidence

`src/pages/Contact.jsx` passes `Contact Us`, the full Hammersmith/Heston/Hounslow description, `/contact`, and no explicit image to `Seo`; therefore `Seo.jsx` supplies its default `teacher-hug.webp` image and expands the browser/OG title to `Contact Us - Alexandra Montessori`. `useSearchParams()` also reads `?event=` and prefills the enquiry message.

### Current public section -> exact WP canvas parity

| Current public truth | Required WordPress canvas result |
| --- | --- |
| Head output | Full title suffix, description, canonical `/contact`, default OG image, `og:type=website`, site name and Twitter large-card tags render with the same resolved values. |
| Route fixture | Canvas URL is `/contact`; a separate `?event=Open morning` fixture proves the form prefill without putting event text into metadata. |
| Visual boundary | SEO/share previews are editor chrome only; they must not create a visible page section or alter the H1. |

### Editable elements and controls

Title prefix, meta description, optional explicit share image, share-image focal preview, OG title/description override, robots visibility, and an event-prefill test value. Canonical is view-only.

### Layers, reorder, and dragging

This is page-level settings, not a draggable canvas layer. The SEO panel may be reordered in editor chrome, but metadata nodes have a locked semantic emission order and cannot be dragged into visible content.

### Responsive behavior

Offer desktop search, mobile search, and social-card crop previews. Metadata values are one published source across breakpoints; only the preview framing changes.

### Data ownership and bindings

Page-owned SEO copy binds to the Contact route. The site-name suffix and fallback image are global `Seo` defaults. Nursery facts stay in Nursery records; event text is request state and is never persisted into SEO.

### Protected behavior

Route `/contact`, title suffixing, URL sanitization, head-tag uniqueness, `?event=` prefill, and noindex rules are system-owned. A visual edit cannot publish duplicate canonicals or user query text.

### Accessibility

Warn on vague/duplicated titles and descriptions. The share image receives an internal media description, but no fake HTML `alt` is emitted for an OG tag. Preview controls have labelled inputs and keyboard order.

### Relevant state previews

Preview seeded metadata, custom share image, missing optional description, noindex draft, duplicate-title warning, and event-query fixture.

### Storage and versioning

Plan a versioned `seo` object in the Contact page document with `schemaVersion`, draft/published snapshots, revision author/time, and rollback. Event query values and preview form data are excluded from every revision.

### Planned implementation files (future only)

These paths describe later implementation ownership; they are **not created by this planning phase**.

- `includes/definitions/pages/contact.php` — schema/defaults for Contact SEO and locked route.
- `includes/renderers/seo.php` — escaped canonical/OG/Twitter output shared by page previews and public rendering.
- `assets/editor/pages/contact/seo.js` — SERP/share previews and event-query test fixture.
- `tests/parity/contact/seo.spec.js` — resolved-head and `?event=` regression checks.

### Acceptance checklist

- [ ] Fresh seed resolves exactly to the current `Seo.jsx` output, including the default image.
- [ ] Published and same-width preview head values are identical; draft values remain authenticated.
- [ ] The event query still prefills only the message and never leaks into head tags or revisions.


## Intent and feeling

The contact route should feel reassuring and immediate: parents can see all three settings, choose the right destination, and send a message without uncertainty. Search metadata should describe the real contact choices without becoming another hidden copy source.

## Current evidence

The React page owns title `Contact Us`, a contact-focused description, and canonical path `/contact`. Event detail links may arrive with `?event=<title>` and prefill the message.

## Editing experience

- A Page settings drawer edits browser title, meta description, social preview image, canonical path preview, search visibility, and optional share title/description.
- A live SERP/social preview sits beside the fields.
- Event query-prefill behavior appears as a protected badge with a test link, not an editable URL fragment.
- The editor warns when title/description are empty, duplicated, or outside recommended length; a Publisher can intentionally continue after acknowledging non-critical warnings.
- Canonical route is locked here. Route changes belong to the page/redirect workflow.

## Ownership and storage

Store SEO in the Contact page document under a versioned `seo` object. Never infer published metadata from the visible H1 at runtime. Draft preview uses draft metadata; public output reads the published snapshot.

## Accessibility and safety

The social image requires alternative/meaning notes for internal governance but remains decorative in metadata. Unsafe schemes and non-site canonical URLs are rejected.

## Acceptance

- Draft metadata appears only in authenticated preview.
- Public title, description, canonical, and Open Graph values match the published snapshot.
- `?event=` still prefills the form after any visual edit.
- Route editing cannot accidentally break event booking links.
