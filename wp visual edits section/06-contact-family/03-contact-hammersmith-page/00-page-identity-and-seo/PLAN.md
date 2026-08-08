# Contact Hammersmith Identity and SEO Plan

## Implementation-grade parity contract

### Intent and feel

This branch result must feel unmistakably local: parents should see Hammersmith facts in search/share output without creating a second editable copy of the Nursery record.

### Exact current React/public evidence

ContactLocation.jsx resolves the slug through locationBySlug and passes title `Contact Hammersmith`, description `Get in touch with the Alexandra Montessori Hammersmith nursery - Dalling Road, London W6 0EU. Call 0204 618 3477 or send an enquiry to book a show-around.`, path `/contact/hammersmith`, and the bound `classroom-calm.webp` Nursery image to Seo.jsx. Seo adds the Alexandra Montessori title suffix and absolute asset URL.

### Current public section -> exact WP canvas parity

| Current public truth | Required WordPress canvas result |
| --- | --- |
| Resolved metadata | Canvas must resolve Hammersmith, Dalling Road, London W6 0EU, 0204 618 3477, canonical `/contact/hammersmith`, and the current Nursery image exactly, including the global title suffix. |
| Binding visibility | Show sentence fragments and read-only token chips for `{nursery_name}`, `{address}`, `{phone}`, and `{image}`; never flatten those facts silently. |
| Record context | Use the Hammersmith draft record in authenticated preview and the Hammersmith published record on the public route. |

### Editable elements and controls

Static sentence fragments, search/social title and description patterns, optional page-specific social image, crop preview, visibility and share-card text. Fact tokens offer an Edit Nursery source shortcut; canonical is view-only.

### Layers, reorder, and dragging

SEO is page settings, not a canvas layer. Metadata tag order and single-canonical emission are locked; no item may be dragged into visible page sections.

### Responsive behavior

Preview desktop/mobile search snippets and social cards, including long-address wrapping and image crops. One metadata source publishes for every viewport.

### Data ownership and bindings

Branch name, area, address, phone and default social image belong to the Hammersmith Nursery record. Sentence patterns belong to the contact-location template; route/site suffix belong to the registry/global SEO renderer.

### Protected behavior

Stable slug `hammersmith`, canonical path, token escaping, title suffix, absolute image resolution and head-tag uniqueness. A local override is explicit, permissioned and reversible.

### Accessibility

Editor fields are labelled and warnings do not depend on colour. Social-image meaning is documented internally; metadata cannot inject HTML. Titles/descriptions remain understandable when read without image.

### Relevant state previews

Preview exact Hammersmith seed, draft Nursery change, missing image fallback, long title, missing optional description, noindex draft and invalid-token/source warning.

### Storage and versioning

Store the template SEO pattern once under versioned `contact_location.seo`, plus an explicit keyed hammersmith override only when used. Nursery facts remain record-owned. Revisions show resolved before/after values and permit rollback without duplicating facts.

### Planned implementation files (future only)

These paths describe later implementation ownership; they are **not created by this planning phase**.

- `includes/definitions/templates/contact-location.php` -- tokenized branch SEO schema/default.
- `includes/renderers/seo.php` -- safe branch-aware head output.
- `assets/editor/templates/contact-location/hammersmith-seo.js` -- Hammersmith resolved preview fixture.
- `tests/parity/contact-location/hammersmith-seo.spec.js` -- Hammersmith metadata/binding regression.

### Acceptance checklist

- [ ] Fresh seed resolves the exact current Hammersmith title, sentence, route and image.
- [ ] Changing the Hammersmith record updates the resolved preview/public metadata without editing the template.
- [ ] Draft/local overrides are clearly labelled, versioned and never leak before publish.


## Exact current output

Metadata resolves `Contact Hammersmith`, the Dalling Road/W6 contact description, `/contact/hammersmith`, and Hammersmith's Nursery image. The branch area is Ravenscourt but the public Nursery name remains Hammersmith.

## Exact WP parity and controls

The canvas loads the real route with Hammersmith context. SEO copy can combine static phrases with `{nursery_name}`, `{area}`, `{address}`, `{phone}`, and `{image}` tokens. The editor previews resolved search/social cards and labels every bound/local value.

## Guardrails and acceptance

Do not silently replace Hammersmith with Ravenscourt: each token has distinct meaning. Canonical route is protected. Source updates propagate, and draft metadata remains authenticated until publication.
