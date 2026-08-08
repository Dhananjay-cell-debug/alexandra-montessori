# Nursery Contact Directory Plan

## Implementation-grade parity contract

### Intent and feel

The three branch cards should feel equal, current, and immediately actionable so a parent can compare locations without wondering which facts are stale.

### Exact current React/public evidence

`Contact.jsx` maps `locations` into a `md:grid-cols-3` grid. Each `Reveal` card shows name, address, `telHref(phone)`, Gmail-compose email, and uppercase `Ages {ageRange}` in a rounded sage-50 card with border, padding and soft shadow; delays are `index * 80`.

### Current public section -> exact WP canvas parity

| Current public truth | Required WordPress canvas result |
| --- | --- |
| Collection | Use the current published Nursery record order and render Hounslow, Heston, Hammersmith when those are the supplied records. |
| Card fields/actions | Resolve the five current visible fields and preserve `tel:` normalization, Gmail compose URL, new-tab email safety attributes, wrapping and reveal delays. |
| Layout/style | Seed one column below `md`, three columns from `md`, `gap-4`, sage-50 surface, sage-100 border, rounded-4xl, `p-6`, and soft shadow. |

### Editable elements and controls

Data subset/order, field visibility, tokenized age prefix, labels, optional hours/detail link, card columns/gap, surface, border, radius, padding, shadow, typography, link style, and animation delay. Record-value selection offers `Edit source`, never covert static replacement.

### Layers, reorder, and dragging

Section > grid > bound card > name/address/phone/email/age. Cards are sortable by handle and keyboard; fields reorder only in semantic flow. Absolute dragging is limited to optional decoration and cannot detach an action from its accessible label.

### Responsive behavior

Seed stacks at mobile and becomes three columns at `md`. Permit bounded 1–4 columns and gaps per device, while long addresses/emails wrap and cards remain natural height at 320px and 200% zoom.

### Data ownership and bindings

Name, address, phone, email, age range and branch identity bind to Nursery records. Collection query/order and visual labels/styles are Contact-section owned. Phone/Gmail URL construction is shared functional code.

### Protected behavior

Stable Nursery IDs, URL construction, target/rel safety, sanitization, publication filtering, and dependency propagation. Reordering display must not silently rewrite canonical Nursery record order.

### Accessibility

Each card uses a real heading; phone/email link text names the destination; focus indicators remain visible; external email semantics are announced where the design pattern requires it. Animation respects reduced motion.

### Relevant state previews

Preview current three records, one record, future fourth record, missing phone, missing email, unpublished record, zero records, long address, and failed media/icon optional state.

### Storage and versioning

Store only query, display order, labels and style in versioned `contact.nursery_directory`. Nursery facts remain normalized records and are resolved at render time; revisions record binding IDs, not duplicated personal/business data.

### Planned implementation files (future only)

These paths describe later implementation ownership; they are **not created by this planning phase**.

- `includes/definitions/pages/contact.php` — directory query and card-template schema.
- `includes/renderers/sections/contact-directory.php` — bound Nursery loop and safe actions.
- `assets/editor/pages/contact/contact-directory.js` — source chips, repeater ordering and state fixtures.
- `tests/parity/contact/contact-directory.spec.js` — three-branch data, link and breakpoint parity.

### Acceptance checklist

- [ ] All current values and card order resolve exactly from `site.js`/published Nursery data.
- [ ] Keyboard and drag reordering persist without breaking the source bindings.
- [ ] Missing/incomplete data never prints `undefined`, dead links, or an empty inaccessible grid.


## Intent and feeling

This section should let a parent scan Hounslow, Heston, and Hammersmith as equal, trustworthy choices before completing the enquiry form.

## Current evidence

The current three-card grid displays each Nursery record's name, address, phone, email, and age range. Phone/email actions use normalized contact helpers.

## Editing model

The grid is a dynamic Nursery Directory block, not three duplicated static cards.

- Data panel: source `Published nurseries`, order, manual featured subset, maximum items, and empty-state choice.
- Card field toggles: name, address, phone, email, age range, opening hours, branch detail link, icon/image.
- Card style: background, border, radius, shadow, padding, hover/focus state, equal height, icon treatment, and alignment.
- Layout: 1-4 columns within safe bounds, gap, contained/wide width, grid/list/carousel presentation, and per-breakpoint stacking.
- Selecting a bound phone/email shows `From Nursery record` and an `Edit Hounslow/Heston/Hammersmith` shortcut. Converting factual contact values to static copy requires explicit confirmation and is discouraged.

## States

Preview normal, one-record, many-record, unpublished-record, incomplete-record, and no-nursery states. Missing phone/email hides only that action and creates a readiness warning on the Nursery record.

## Accessibility

Phone and email remain real links with understandable names. Heading levels inside cards follow the page outline. Carousel mode must include keyboard controls, status, pause, and non-carousel mobile fallback if motion becomes excessive.

## Acceptance

- A new published nursery appears without rebuilding the page.
- Reordering cards never changes Nursery record order unless the editor chooses `Save order to records` with permission.
- Bound edits propagate to footer, branch contact, availability, and nursery pages.
- Cards remain readable at 320px, 390px, 768px, and desktop widths.
