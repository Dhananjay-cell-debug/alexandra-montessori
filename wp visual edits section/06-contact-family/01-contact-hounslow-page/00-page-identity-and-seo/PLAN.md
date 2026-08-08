# Contact Hounslow Identity and SEO Plan

## Implementation-grade parity contract

### Intent and feel

This branch result must feel unmistakably local: parents should see Hounslow facts in search/share output without creating a second editable copy of the Nursery record.

### Exact current React/public evidence

ContactLocation.jsx resolves the slug through locationBySlug and passes title `Contact Hounslow`, description `Get in touch with the Alexandra Montessori Hounslow nursery - Ved Court, Alexandra Road, Hounslow TW3 1LS. Call 0208 001 5165 or send an enquiry to book a show-around.`, path `/contact/hounslow`, and the bound `classroom-main.webp` Nursery image to Seo.jsx. Seo adds the Alexandra Montessori title suffix and absolute asset URL.

### Current public section -> exact WP canvas parity

| Current public truth | Required WordPress canvas result |
| --- | --- |
| Resolved metadata | Canvas must resolve Hounslow, Ved Court, Alexandra Road, Hounslow TW3 1LS, 0208 001 5165, canonical `/contact/hounslow`, and the current Nursery image exactly, including the global title suffix. |
| Binding visibility | Show sentence fragments and read-only token chips for `{nursery_name}`, `{address}`, `{phone}`, and `{image}`; never flatten those facts silently. |
| Record context | Use the Hounslow draft record in authenticated preview and the Hounslow published record on the public route. |

### Editable elements and controls

Static sentence fragments, search/social title and description patterns, optional page-specific social image, crop preview, visibility and share-card text. Fact tokens offer an Edit Nursery source shortcut; canonical is view-only.

### Layers, reorder, and dragging

SEO is page settings, not a canvas layer. Metadata tag order and single-canonical emission are locked; no item may be dragged into visible page sections.

### Responsive behavior

Preview desktop/mobile search snippets and social cards, including long-address wrapping and image crops. One metadata source publishes for every viewport.

### Data ownership and bindings

Branch name, area, address, phone and default social image belong to the Hounslow Nursery record. Sentence patterns belong to the contact-location template; route/site suffix belong to the registry/global SEO renderer.

### Protected behavior

Stable slug `hounslow`, canonical path, token escaping, title suffix, absolute image resolution and head-tag uniqueness. A local override is explicit, permissioned and reversible.

### Accessibility

Editor fields are labelled and warnings do not depend on colour. Social-image meaning is documented internally; metadata cannot inject HTML. Titles/descriptions remain understandable when read without image.

### Relevant state previews

Preview exact Hounslow seed, draft Nursery change, missing image fallback, long title, missing optional description, noindex draft and invalid-token/source warning.

### Storage and versioning

Store the template SEO pattern once under versioned `contact_location.seo`, plus an explicit keyed hounslow override only when used. Nursery facts remain record-owned. Revisions show resolved before/after values and permit rollback without duplicating facts.

### Planned implementation files (future only)

These paths describe later implementation ownership; they are **not created by this planning phase**.

- `includes/definitions/templates/contact-location.php` -- tokenized branch SEO schema/default.
- `includes/renderers/seo.php` -- safe branch-aware head output.
- `assets/editor/templates/contact-location/hounslow-seo.js` -- Hounslow resolved preview fixture.
- `tests/parity/contact-location/hounslow-seo.spec.js` -- Hounslow metadata/binding regression.

### Acceptance checklist

- [ ] Fresh seed resolves the exact current Hounslow title, sentence, route and image.
- [ ] Changing the Hounslow record updates the resolved preview/public metadata without editing the template.
- [ ] Draft/local overrides are clearly labelled, versioned and never leak before publish.


## Exact current public output

`ContactLocation.jsx` derives title `Contact Hounslow`, description from Hounslow address and phone, path `/contact/hounslow`, and social image from the Hounslow Nursery image. This is not generic Contact metadata.

## Exact WordPress parity

The WP page selector opens `Contact > Hounslow`, then renders `/contact/hounslow` in the live canvas using the Hounslow record fixture. The settings drawer shows bound tokens for name, address, phone, and image. It previews resolved text while preserving bindings.

## Editing controls

- edit static sentence fragments around `{nursery_name}`, `{address}`, and `{phone}`;
- choose social image binding or an explicit Hounslow-only override;
- edit social title/description and search visibility;
- preview canonical, Open Graph, and mobile share card.

## Protected ownership

Route and nursery identity are registry-owned. Hounslow facts are Nursery-record-owned. Detaching a factual token requires an administrator, a reason, and a visible Local override badge.

## Acceptance

- Preview resolves Ved Court, Alexandra Road, Hounslow TW3 1LS and the published Hounslow phone record.
- Updating the Nursery record updates page metadata without editing this page.
- Draft SEO is not exposed publicly until published.
