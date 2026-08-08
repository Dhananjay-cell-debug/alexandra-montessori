# Contact Hounslow Enquiry Form and Primary Photo Plan

## Implementation-grade parity contract

### Intent and feel

The enquiry area should feel immediately ready for Hounslow: the branch is already chosen, the real nursery image reassures the parent, and no visual freedom can weaken the form.

### Exact current React/public evidence

ContactLocation.jsx renders a `lg:grid-cols-12` section with ContactForm in `lg:col-span-7` and a right rail in `lg:col-span-5`. It passes `defaultBranch="hounslow"`. The first rail item uses Nursery image `classroom-main.webp`, alt `The Alexandra Montessori Hounslow nursery`, `aspect-[16/10]`, full width, followed by the contact and gallery cards.

### Current public section -> exact WP canvas parity

| Current public truth | Required WordPress canvas result |
| --- | --- |
| Branch/form | The real form loads with stable branch ID `hounslow` selected and current Hounslow routing helper visible. |
| Primary photo | Resolve `classroom-main.webp` from the Hounslow Nursery record with the current 16:10 frame and contextual alt. |
| Layout | Seed 7/5 columns at `lg`, 40px grid gap, form left and spaced right rail; stack in current DOM order below `lg`. |

### Editable elements and controls

Section/container spacing, desktop column ratio, gap, rail gap, mobile order, form presentation controls, and primary photo media/focal point/aspect/radius/alt/responsive source. Source vs local media override is explicit.

### Layers, reorder, and dragging

Section > two-column grid > form region + rail; rail starts with photo then details then gallery. Page-level handles may swap complete regions in an approved preset; fields remain flow-only and rail cards reorder with DOM order. The photo can be repositioned only through crop/focal controls, not absolute free drag.

### Responsive behavior

Seed stacks form then full rail below `lg`; image keeps 16:10 and natural width. Test 320/390/768/desktop, long form errors, zoom and focal point per device. A visual mobile order override also changes DOM order.

### Data ownership and bindings

Branch ID, Nursery image and record facts bind to Hounslow; form labels/layout belong to the shared enquiry-form configuration; local photo override/style belongs to hounslow contact template instance.

### Protected behavior

Default branch, recipient routing, field keys/types, validation, consent, honeypot, loaded time, idempotency, REST endpoint and server retention. Image replacement cannot change branch identity or form selection.

### Accessibility

Logical labels/focus/error announcements remain intact. Meaningful photo has maintained alt; crop cannot hide the subject without warning. Form and rail order stays coherent for screen readers.

### Relevant state previews

Preview exact Hounslow loaded state, image missing/fallback, long alt, invalid fields, sending/error/success, narrow mobile, swapped approved layout and reduced motion.

### Storage and versioning

Store section/grid/photo presentation under versioned `contact_location.hounslow.enquiry_media`; retain form contract version separately. Nursery media stays source-owned, and neither form inputs nor synthetic test PII enters revisions.

### Planned implementation files (future only)

These paths describe later implementation ownership; they are **not created by this planning phase**.

- `includes/definitions/templates/contact-location.php` -- grid/media instance schema.
- `includes/renderers/sections/contact-location-enquiry.php` -- bound form/rail composition.
- `assets/editor/templates/contact-location/hounslow-enquiry-media.js` -- Hounslow layout/media controls and states.
- `tests/parity/contact-location/hounslow-enquiry-media.spec.js` -- preselection, image and responsive order checks.

### Acceptance checklist

- [ ] Hounslow is selected before interaction and a synthetic test follows the correct routing contract.
- [ ] The current classroom-main.webp image, 16:10 framing and 7/5 layout match public output.
- [ ] Visual edits preserve field DOM order, consent, validation, mobile flow and data boundaries.


## Exact current public section

The first content section uses a 7/5 desktop grid: `ContactForm` on the left and a right rail beginning with the Hounslow Nursery image. The form preselects `defaultBranch="hounslow"`. Current Hounslow primary media is the bound Nursery image.

## Exact WordPress parity

The canvas must show the actual populated Hounslow selection, the actual form component, and the same image crop. A generic empty form preview is not acceptable.

## Editable presentation

- grid ratio, gap, max width, vertical spacing, mobile order, and surface background;
- form label/help/placeholder copy and approved field ordering in flow layout;
- field/border/focus/error/button styles;
- primary photo replacement at the Hounslow Nursery source or a clearly labelled page-only visual override;
- image aspect ratio, focal point, crop, radius, alt text, and responsive source.

## Protected behavior

Branch preselection, submission route, form field keys, validation, consent, anti-spam, idempotency, and Hounslow routing remain locked. Fields cannot be layered or visually reordered away from DOM order.

## Acceptance

- Hounslow is preselected on initial load and receives test-mode routing.
- Image crop matches at desktop/tablet/mobile.
- Keyboard focus follows the visual field order.
