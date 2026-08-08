# Contact Heston Identity and SEO Plan

## Implementation-grade parity contract

### Intent and feel

This branch result must feel unmistakably local: parents should see Heston facts in search/share output without creating a second editable copy of the Nursery record.

### Exact current React/public evidence

ContactLocation.jsx resolves the slug through locationBySlug and passes title `Contact Heston`, description `Get in touch with the Alexandra Montessori Heston nursery - 36 Springwell Road, Hounslow TW5 9EJ. Call 0203 627 6707 or send an enquiry to book a show-around.`, path `/contact/heston`, and the bound `friends-two.webp` Nursery image to Seo.jsx. Seo adds the Alexandra Montessori title suffix and absolute asset URL.

### Current public section -> exact WP canvas parity

| Current public truth | Required WordPress canvas result |
| --- | --- |
| Resolved metadata | Canvas must resolve Heston, 36 Springwell Road, Hounslow TW5 9EJ, 0203 627 6707, canonical `/contact/heston`, and the current Nursery image exactly, including the global title suffix. |
| Binding visibility | Show sentence fragments and read-only token chips for `{nursery_name}`, `{address}`, `{phone}`, and `{image}`; never flatten those facts silently. |
| Record context | Use the Heston draft record in authenticated preview and the Heston published record on the public route. |

### Editable elements and controls

Static sentence fragments, search/social title and description patterns, optional page-specific social image, crop preview, visibility and share-card text. Fact tokens offer an Edit Nursery source shortcut; canonical is view-only.

### Layers, reorder, and dragging

SEO is page settings, not a canvas layer. Metadata tag order and single-canonical emission are locked; no item may be dragged into visible page sections.

### Responsive behavior

Preview desktop/mobile search snippets and social cards, including long-address wrapping and image crops. One metadata source publishes for every viewport.

### Data ownership and bindings

Branch name, area, address, phone and default social image belong to the Heston Nursery record. Sentence patterns belong to the contact-location template; route/site suffix belong to the registry/global SEO renderer.

### Protected behavior

Stable slug `heston`, canonical path, token escaping, title suffix, absolute image resolution and head-tag uniqueness. A local override is explicit, permissioned and reversible.

### Accessibility

Editor fields are labelled and warnings do not depend on colour. Social-image meaning is documented internally; metadata cannot inject HTML. Titles/descriptions remain understandable when read without image.

### Relevant state previews

Preview exact Heston seed, draft Nursery change, missing image fallback, long title, missing optional description, noindex draft and invalid-token/source warning.

### Storage and versioning

Store the template SEO pattern once under versioned `contact_location.seo`, plus an explicit keyed heston override only when used. Nursery facts remain record-owned. Revisions show resolved before/after values and permit rollback without duplicating facts.

### Planned implementation files (future only)

These paths describe later implementation ownership; they are **not created by this planning phase**.

- `includes/definitions/templates/contact-location.php` -- tokenized branch SEO schema/default.
- `includes/renderers/seo.php` -- safe branch-aware head output.
- `assets/editor/templates/contact-location/heston-seo.js` -- Heston resolved preview fixture.
- `tests/parity/contact-location/heston-seo.spec.js` -- Heston metadata/binding regression.

### Acceptance checklist

- [ ] Fresh seed resolves the exact current Heston title, sentence, route and image.
- [ ] Changing the Heston record updates the resolved preview/public metadata without editing the template.
- [ ] Draft/local overrides are clearly labelled, versioned and never leak before publish.


## Exact current output

The route derives `Contact Heston`, a description using the Heston address and phone, `/contact/heston`, and the Heston Nursery image. Current bound facts include 36 Springwell Road, Hounslow TW5 9EJ, Heston contact number/email, and Heston media.

## Exact WP parity and controls

The Pages panel opens the real Heston route with Heston record context. SEO sentence fragments and social presentation are editable around protected `{nursery_name}`, `{address}`, `{phone}`, and `{image}` bindings. A source chip opens Heston record editing; a page-only image override is clearly marked.

## Safety and acceptance

Canonical route is registry-owned. Draft SEO stays private. Updating Heston contact data changes resolved metadata everywhere. Public preview and WordPress preview must resolve the same published/draft Heston fixture respectively.
