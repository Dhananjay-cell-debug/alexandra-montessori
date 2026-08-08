# Heston Other Nurseries Navigation Plan

## Implementation-grade parity contract

### Intent and feel

This closing navigation should make the two alternatives feel helpful, never confuse the current Heston page with a self-link, and automatically remain useful as the Nursery collection grows.

### Exact current React/public evidence

ContactLocation.jsx renders uppercase centred label `Our other nurseries`, then filters `locations` by `l.id !== "heston"`. With current data it creates btn-outline links for Hounslow and Hammersmith to their registered `/contact/{id}` routes, each followed by an ArrowRight icon.

### Current public section -> exact WP canvas parity

| Current public truth | Required WordPress canvas result |
| --- | --- |
| Filter/results | Use `published Nurseries except current Heston`; current result set is exactly Hounslow and Hammersmith. |
| Links | Resolve route-registry destinations from stable IDs and keep visible Nursery name plus ArrowRight in each outline button. |
| Layout | Seed contained `pb-16`, centred uppercase tracked label, `mt-6`, wrapping centred flex row and `gap-3`. |

### Editable elements and controls

Heading, button label pattern, arrow/icon, order, optional manual subset, alignment, wrap, gap, section surface/padding and button visuals. Query and exclusion are clearly shown; manual selection does not convert routes to raw URLs.

### Layers, reorder, and dragging

Section > heading > link collection. Complete link items reorder by drag or keyboard with DOM order; name/icon remain one link. Free positioning is disabled because wrapping and reading order must stay reliable.

### Responsive behavior

Seed naturally wraps and centres. Per-device gap/alignment/button width may change, while targets remain at least 44px and order stays consistent at 320px/200% zoom.

### Data ownership and bindings

Nursery IDs/names/status bind to published Nursery records; contact destinations bind to the route registry. Heading, label pattern, display order and local style belong to the Heston section instance.

### Protected behavior

Always exclude current ID `heston` in default mode, sanitize labels, generate only registered internal routes and suppress unpublished targets. Explicit filter override requires warning; no dead/self link.

### Accessibility

Use actual link text with Nursery names, visible focus and adequate target sizes. Arrow icon is decorative. The group is reachable in logical source order and does not rely on hover.

### Relevant state previews

Preview current two links, one other Nursery, no alternatives, future fourth Nursery, unpublished target, long Nursery name, manual order and current-record identity change.

### Storage and versioning

Store query/filter/order/style under versioned `contact_location.heston.other_nurseries`; keep only Nursery IDs and route references. New record facts resolve dynamically; revisions show query/override changes and rollback.

### Planned implementation files (future only)

These paths describe later implementation ownership; they are **not created by this planning phase**.

- `includes/definitions/templates/contact-location.php` -- other-Nursery query/button schema.
- `includes/renderers/sections/other-nurseries.php` -- current-record exclusion and route links.
- `assets/editor/templates/contact-location/heston-other-nurseries.js` -- query/order/state editor.
- `tests/parity/contact-location/heston-other-nurseries.spec.js` -- exclusion, targets, wrapping and future-record checks.

### Acceptance checklist

- [ ] Only Hounslow and Hammersmith appear for today's Heston route.
- [ ] Default mode never self-links and future published records gain valid contact links automatically.
- [ ] Drag order, keyboard order and route bindings remain identical across breakpoints.


## Exact current section

The dynamic list excludes Heston and links to Hounslow and Hammersmith contact pages.

## Editing model

Edit heading, tokenized button label, arrow/icon, button visuals, alignment, gap, wrapping, surface, and spacing. Data source remains `Published nurseries except current`; manual order is allowed without converting links to static URLs.

## Acceptance

Current output contains Hounslow and Hammersmith only. Route-registry links remain valid, future published nurseries can appear automatically, and each target meets mobile tap-size requirements.
