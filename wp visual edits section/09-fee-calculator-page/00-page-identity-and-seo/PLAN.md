# Fee Calculator Identity and SEO Plan

## Implementation-grade parity contract

### Intent and feel

The result in search and social shares must feel useful but restrained: it is an hours-planning estimate, never a price quote or eligibility decision.

### Exact current React/public evidence

`src/pages/FeeCalculator.jsx` passes `Funding Estimate`, description `Estimate weekly chargeable nursery hours after funded childcare and download the correct branch fee sheet.`, and `/fee-calculator` to `Seo`. No image is passed, so current `Seo.jsx` uses its default `teacher-hug.webp`, expands the visible head title/OG title to `Funding Estimate - Alexandra Montessori`, emits one canonical, website OG tags, and a Twitter large-card tag.

### Current public section -> exact WP canvas parity

| Current public truth | Required WordPress canvas result |
| --- | --- |
| Head values | Resolve the full suffixed title, exact description punctuation, canonical `https://alexandramontessori.co.uk/fee-calculator`, default absolute share image and current OG/Twitter tags. |
| Meaning | Search/share previews say estimate and chargeable hours; they do not add currency, prices, savings, eligibility or a guaranteed bill. |
| Runtime boundary | Changing Nursery/days/funded-hours inputs changes no head tag, canonical or share image and stores no input state in metadata. |

### Editable elements and controls

Search title prefix, meta description, optional explicit social image, social title/description override, crop preview, search visibility and review status. Canonical and the global Alexandra Montessori suffix are read-only. Financial-claim lint highlights words such as guaranteed, free, payable and exact.

### Layers, reorder, and dragging

SEO is page settings, not a visible or draggable layer. Tag emission order, one-title/one-canonical rules and global suffixing are locked; the SEO panel may move in editor chrome without entering the page canvas.

### Responsive behavior

Offer desktop/mobile search snippets and common social-card crops. One published metadata record serves all viewports; responsive controls affect only the preview crop, not device-specific claims.

### Data ownership and bindings

Page-owned SEO copy belongs to the Fee Calculator page document. The site-name suffix/default share image/domain are global SEO settings. Calculator selections/results are ephemeral runtime values and Nursery facts remain Nursery-record owned.

### Protected behavior

Registered route, canonical origin, escaping, URL protocol, unique head tags, default-image resolution and strict exclusion of live input/result values. A design revision cannot capture branch choice, day slider value or funded-hour selection.

### Accessibility

Editor fields and warnings are keyboard-labelled and not colour-only. Social image receives an internal purpose note; OG metadata does not invent an HTML image alt. Copy remains understandable without the preview image.

### Relevant state previews

Preview exact seed, explicit social image, long/duplicate title, missing optional description, noindex draft, invalid external canonical attempt and an input-change fixture proving head stability.

### Storage and versioning

Plan a versioned `fee_calculator.seo` object with schema version, review metadata, draft/published snapshots, author/time, diff and rollback. Store no query/session/calculation values in the page document or revision log.

### Planned implementation files (future only)

These paths describe later implementation ownership; they are **not created by this planning phase**.

- `includes/definitions/pages/fee-calculator.php` -- SEO schema, defaults and locked route.
- `includes/renderers/seo.php` -- shared escaped canonical/OG/Twitter output.
- `assets/editor/pages/fee-calculator/seo.js` -- search/share previews and claim lint.
- `tests/parity/fee-calculator/seo.spec.js` -- exact head output and input-isolation regression.

### Acceptance checklist

- [ ] Fresh seed exactly matches current `Seo.jsx` output, including suffix and default image.
- [ ] Draft metadata remains authenticated until publish and rollback restores the prior complete head snapshot.
- [ ] Calculator interaction never changes or persists SEO/head data.


## Exact current output

Metadata title `Funding Estimate`, description explaining chargeable hours and fee-sheet download, and `/fee-calculator` canonical.

## Editor controls and safety

Edit search/social copy and optional social image with resolved preview. Title/description must continue to describe an estimate rather than a guaranteed invoice; changed financial claims require review acknowledgement. Route is protected because Fees and menus link here.

## Acceptance

Published metadata matches the funded-hours function, draft values stay private, and no calculator input/result is included in metadata or revisions.
