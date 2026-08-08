# Contact Form Introduction Plan

## Implementation-grade parity contract

### Intent and feel

The introduction should feel human and concise, explaining what to send before the parent invests effort in the form.

### Exact current React/public evidence

`Contact.jsx` renders a separate white `py-12 sm:py-16` section. Its centred content is H2 `Send us a message here`, followed by the exact two-line sentence beginning `For booking a visit or a childcare enquiry...`, then the form at `mt-10 max-w-3xl`; reveal delays are 0, 100 and 150ms.

### Current public section -> exact WP canvas parity

| Current public truth | Required WordPress canvas result |
| --- | --- |
| Copy | Render the exact H2 and paragraph punctuation/casing from `Contact.jsx`, with no unseeded eyebrow, icon, response-time chip or privacy note. |
| Placement | Keep intro immediately above `ContactForm` inside the same white section and container. |
| Style/spacing | Seed centred H2 `text-3xl sm:text-4xl`, paragraph `max-w-xl mt-3`, and form wrapper `max-w-3xl mt-10` with current vertical padding. |

### Editable elements and controls

H2, supporting text, optional response/privacy note, alignment, text widths, section surface, padding, gap, divider and reveal timing. Approved presets may place intro beside the form, but the exact-current preset is centred above it.

### Layers, reorder, and dragging

Section > container > intro group > H2/paragraph, then form region. The intro group may reorder only as a unit relative to approved sibling regions. Text stays in flow; decorative icon/background alone may be dragged.

### Responsive behavior

Seed maintains centred text and switches vertical padding at `sm`. Limit paragraph measure, preserve natural height for long copy, and enforce intro-before-fields visual and DOM order on every breakpoint.

### Data ownership and bindings

Introduction copy and local styles belong to this Contact section. Brand, privacy policy and response-time claims, if enabled, use global tokens/review metadata. `ContactForm` remains a separate functional block.

### Protected behavior

The intro cannot replace consent, form accessible name, or submission instructions. HTML is sanitized; unsupported absolute layout and misleading guaranteed response claims are blocked/reviewed.

### Accessibility

Keep a real H2 before the form, sufficient text contrast, comfortable line length, and reduced-motion-safe reveal. If an icon is decorative it is hidden; if informative it needs text equivalent.

### Relevant state previews

Preview exact seed, long translation/copy, empty optional paragraph, response-note review warning, side-by-side desktop preset, 320px and reduced-motion.

### Storage and versioning

Version `contact.form_introduction` separately from the functional form block, including content, style, responsive overrides, review metadata and draft/published snapshots. Form submissions never enter this document.

### Planned implementation files (future only)

These paths describe later implementation ownership; they are **not created by this planning phase**.

- `includes/definitions/pages/contact.php` — intro schema/current copy defaults.
- `includes/renderers/sections/contact-form-introduction.php` — semantic H2/copy wrapper.
- `assets/editor/pages/contact/form-introduction.js` — inline copy/layout controls.
- `tests/parity/contact/form-introduction.spec.js` — copy, order and spacing parity.

### Acceptance checklist

- [ ] Exact current copy and section geometry are the untouched seed.
- [ ] Reordering never separates the intro from the form in mobile DOM order.
- [ ] Empty/long/optional states remain readable and cannot suppress required consent.


## Intent and feeling

Set expectations immediately before the form: what the form is for, how quickly the team will respond, and where the message goes.

## Current evidence

The current section contains heading `Send us a message here` and explanatory copy about visits and childcare enquiries.

## Editable elements and controls

- eyebrow, H2, supporting rich text, optional response-time note, optional privacy reassurance, and optional icon;
- direct text editing, semantic heading choice constrained to the page outline, text width, alignment, spacing, background, and separator;
- the introduction can move with the form as one group or be separated into its own responsive flow region;
- optional page-template variations: centred intro, left intro beside form, or compact banner.

## Responsive behavior

On mobile, keep the introduction immediately before the first form field in DOM and visual order. Maximum line length and spacing are independently adjustable without absolute positioning.

## Guardrails

Claims about response time or data handling show a content-review reminder. This copy cannot replace the required consent text inside the functional form.

## Acceptance

- Inline edits preserve the form's accessible name and focus order.
- Moving the intro never separates it from the form on mobile.
- Empty intro is allowed in draft but produces a clarity warning before publish.
