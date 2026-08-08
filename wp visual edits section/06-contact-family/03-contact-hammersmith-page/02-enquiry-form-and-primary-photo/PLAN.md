# Contact Hammersmith Enquiry Form and Primary Photo Plan

## Implementation-grade parity contract

### Intent and feel

The enquiry area should feel immediately ready for Hammersmith: the branch is already chosen, the real nursery image reassures the parent, and no visual freedom can weaken the form.

### Exact current React/public evidence

ContactLocation.jsx renders a `lg:grid-cols-12` section with ContactForm in `lg:col-span-7` and a right rail in `lg:col-span-5`. It passes `defaultBranch="hammersmith"`. The first rail item uses Nursery image `classroom-calm.webp`, alt `The Alexandra Montessori Hammersmith nursery`, `aspect-[16/10]`, full width, followed by the contact and gallery cards.

### Current public section -> exact WP canvas parity

| Current public truth | Required WordPress canvas result |
| --- | --- |
| Branch/form | The real form loads with stable branch ID `hammersmith` selected and current Hammersmith routing helper visible. |
| Primary photo | Resolve `classroom-calm.webp` from the Hammersmith Nursery record with the current 16:10 frame and contextual alt. |
| Layout | Seed 7/5 columns at `lg`, 40px grid gap, form left and spaced right rail; stack in current DOM order below `lg`. |

### Editable elements and controls

Section/container spacing, desktop column ratio, gap, rail gap, mobile order, form presentation controls, and primary photo media/focal point/aspect/radius/alt/responsive source. Source vs local media override is explicit.

### Layers, reorder, and dragging

Section > two-column grid > form region + rail; rail starts with photo then details then gallery. Page-level handles may swap complete regions in an approved preset; fields remain flow-only and rail cards reorder with DOM order. The photo can be repositioned only through crop/focal controls, not absolute free drag.

### Responsive behavior

Seed stacks form then full rail below `lg`; image keeps 16:10 and natural width. Test 320/390/768/desktop, long form errors, zoom and focal point per device. A visual mobile order override also changes DOM order.

### Data ownership and bindings

Branch ID, Nursery image and record facts bind to Hammersmith; form labels/layout belong to the shared enquiry-form configuration; local photo override/style belongs to hammersmith contact template instance.

### Protected behavior

Default branch, recipient routing, field keys/types, validation, consent, honeypot, loaded time, idempotency, REST endpoint and server retention. Image replacement cannot change branch identity or form selection.

### Accessibility

Logical labels/focus/error announcements remain intact. Meaningful photo has maintained alt; crop cannot hide the subject without warning. Form and rail order stays coherent for screen readers.

### Relevant state previews

Preview exact Hammersmith loaded state, image missing/fallback, long alt, invalid fields, sending/error/success, narrow mobile, swapped approved layout and reduced motion.

### Storage and versioning

Store section/grid/photo presentation under versioned `contact_location.hammersmith.enquiry_media`; retain form contract version separately. Nursery media stays source-owned, and neither form inputs nor synthetic test PII enters revisions.

### Planned implementation files (future only)

These paths describe later implementation ownership; they are **not created by this planning phase**.

- `includes/definitions/templates/contact-location.php` -- grid/media instance schema.
- `includes/renderers/sections/contact-location-enquiry.php` -- bound form/rail composition.
- `assets/editor/templates/contact-location/hammersmith-enquiry-media.js` -- Hammersmith layout/media controls and states.
- `tests/parity/contact-location/hammersmith-enquiry-media.spec.js` -- preselection, image and responsive order checks.

### Acceptance checklist

- [ ] Hammersmith is selected before interaction and a synthetic test follows the correct routing contract.
- [ ] The current classroom-calm.webp image, 16:10 framing and 7/5 layout match public output.
- [ ] Visual edits preserve field DOM order, consent, validation, mobile flow and data boundaries.


## Exact current section

The 7/5 desktop grid renders `ContactForm defaultBranch="hammersmith"` and the Hammersmith primary Nursery image (`classroom-calm.webp` in the fallback model).

## Editable presentation

Section grid, gap, spacing, mobile order, form styles/copy, approved field flow, and photo crop/focal/aspect/radius/alt/mobile source are editable. Choosing `Edit source` changes the Hammersmith Nursery image; `Override only here` is visibly local.

## Protected behavior

Hammersmith selection/routing, validation, consent, anti-spam, field keys, API, and submission identity remain locked. Form fields never enter freeform layered mode.

## Acceptance

Initial branch is Hammersmith, synthetic routing uses its destination, and the classroom image remains visually correct across breakpoints.
