# Hounslow Other Nurseries Navigation Plan

## Implementation-grade parity contract

### Intent and feel

This closing navigation should make the two alternatives feel helpful, never confuse the current Hounslow page with a self-link, and automatically remain useful as the Nursery collection grows.

### Exact current React/public evidence

ContactLocation.jsx renders uppercase centred label `Our other nurseries`, then filters `locations` by `l.id !== "hounslow"`. With current data it creates btn-outline links for Heston and Hammersmith to their registered `/contact/{id}` routes, each followed by an ArrowRight icon.

### Current public section -> exact WP canvas parity

| Current public truth | Required WordPress canvas result |
| --- | --- |
| Filter/results | Use `published Nurseries except current Hounslow`; current result set is exactly Heston and Hammersmith. |
| Links | Resolve route-registry destinations from stable IDs and keep visible Nursery name plus ArrowRight in each outline button. |
| Layout | Seed contained `pb-16`, centred uppercase tracked label, `mt-6`, wrapping centred flex row and `gap-3`. |

### Editable elements and controls

Heading, button label pattern, arrow/icon, order, optional manual subset, alignment, wrap, gap, section surface/padding and button visuals. Query and exclusion are clearly shown; manual selection does not convert routes to raw URLs.

### Layers, reorder, and dragging

Section > heading > link collection. Complete link items reorder by drag or keyboard with DOM order; name/icon remain one link. Free positioning is disabled because wrapping and reading order must stay reliable.

### Responsive behavior

Seed naturally wraps and centres. Per-device gap/alignment/button width may change, while targets remain at least 44px and order stays consistent at 320px/200% zoom.

### Data ownership and bindings

Nursery IDs/names/status bind to published Nursery records; contact destinations bind to the route registry. Heading, label pattern, display order and local style belong to the Hounslow section instance.

### Protected behavior

Always exclude current ID `hounslow` in default mode, sanitize labels, generate only registered internal routes and suppress unpublished targets. Explicit filter override requires warning; no dead/self link.

### Accessibility

Use actual link text with Nursery names, visible focus and adequate target sizes. Arrow icon is decorative. The group is reachable in logical source order and does not rely on hover.

### Relevant state previews

Preview current two links, one other Nursery, no alternatives, future fourth Nursery, unpublished target, long Nursery name, manual order and current-record identity change.

### Storage and versioning

Store query/filter/order/style under versioned `contact_location.hounslow.other_nurseries`; keep only Nursery IDs and route references. New record facts resolve dynamically; revisions show query/override changes and rollback.

### Planned implementation files (future only)

These paths describe later implementation ownership; they are **not created by this planning phase**.

- `includes/definitions/templates/contact-location.php` -- other-Nursery query/button schema.
- `includes/renderers/sections/other-nurseries.php` -- current-record exclusion and route links.
- `assets/editor/templates/contact-location/hounslow-other-nurseries.js` -- query/order/state editor.
- `tests/parity/contact-location/hounslow-other-nurseries.spec.js` -- exclusion, targets, wrapping and future-record checks.

### Acceptance checklist

- [ ] Only Heston and Hammersmith appear for today's Hounslow route.
- [ ] Default mode never self-links and future published records gain valid contact links automatically.
- [ ] Drag order, keyboard order and route bindings remain identical across breakpoints.


## Exact current public section

Below the content grid, `Our other nurseries` dynamically excludes Hounslow and links to Heston and Hammersmith contact routes with arrow icons.

## Editing model

Heading label, button label pattern, arrow/icon, button style, alignment, gap, background, and vertical spacing are editable. Source/filter are displayed as `Published nurseries except current`. Editors may choose manual order but cannot accidentally re-include Hounslow unless they explicitly change the filter.

## Responsive/accessibility

Buttons wrap naturally, retain minimum 44px target height, and expose nursery names in link text. Link destinations are route-registry bindings, not freehand URLs.

## States

With no other nursery, hide the entire section or show an approved Contact action. With one nursery, centre one button. Future branches appear automatically unless manual selection is enabled.

## Acceptance

- Only Heston and Hammersmith appear for the current three-branch dataset.
- Adding a future published Nursery produces a valid contact link automatically.
- Hounslow never links to itself in default mode.
