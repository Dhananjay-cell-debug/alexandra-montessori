# Hounslow — testimonials section

## Intent and feel

Preserve the current cross-site parent-story consistency while making the Hounslow heading and safe presentation editable.

## Current public section → exact WP canvas parity

- Eleventh public section renders only when the resolved testimonial collection is non-empty.
- Current logic deliberately takes the first three CMS/static testimonials to match the homepage, then renders `What parents say` / `Families love Hounslow`, three five-star quote cards, and `Read all parent stories` linking `/testimonials`.
- The first three currently identify Hammersmith, Heston, and Hounslow parents; they are not filtered to Hounslow.

## Editable elements and controls

- Hounslow heading/CTA label, section/card tokens, and a read-only-by-default bound testimonial list with preview of homepage parity.
- Testimonial content editing routes to the shared testimonial entity editor; selection/order change requires a visible `break homepage parity` decision and permission.

## Layers and dragging

- Exact tree: `Testimonials section` → `Heading group`; `Bound testimonial grid` → three `Testimonial card` → `Stars`, `Quote icon`, `Quote`, `Attribution`; `All stories action`.
- Default card dragging is locked to shared homepage order; if policy unlocks it, reorder updates an explicit Hounslow selection only and has keyboard parity.

## Responsive behaviour

- One column until `md`, then three; cards remain equal-height through flexible quote body without truncation.
- Long quotes and 200% zoom expand vertically; no carousel-only access is introduced.

## Record/template binding and overrides

- Testimonial entities and default first-three selection are shared; Hounslow owns only heading/CTA/presentation overrides unless selection policy is explicitly changed.
- Attribution/location remain owned by each testimonial, never copied into Hounslow nursery facts.

## Protected rules

- Preserve client-requested homepage match by default; quotes/attributions require consent/moderation status and plain text.
- Five-star visual cannot become an unsupported editable rating per branch without schema/policy change.

## Accessibility

- Quotes use readable text and attribution; stars are hidden or labelled once as rating, not announced five times.
- Focus, contrast, DOM order, and reduced motion pass.

## Empty and error states

- Zero resolved testimonials hides the public section exactly as JSX does and shows an admin-only bound-collection warning.
- Failed CMS load uses last published moderated snapshot, never unmoderated drafts.

## Storage and versioning

- Store Hounslow presentation at `nurseries/{hounslowUuid}/sections/testimonials`; shared selection snapshot records testimonial IDs and revisions.
- Revision history shows whether change came from shared entity, selection policy, or Hounslow heading/style.

## Planned implementation files (future only)

- `am-visual-builder/schema/entities/nursery/sections/testimonials.php`
- `am-visual-builder/admin/pages/nursery/sections/TestimonialsEditor.jsx`
- `am-visual-builder/runtime/nursery/TestimonialsSection.php`
- `am-visual-builder/content/nurseries/hounslow/testimonials.json`

## Acceptance checklist

- [ ] Current first-three/homepage parity, Hounslow title, cards, and `/testimonials` action reproduce.
- [ ] Cross-location attributions remain accurate and are not auto-filtered.
- [ ] Consent, empty/load-error state, responsive expansion, stars accessibility, diff, and restore pass.

