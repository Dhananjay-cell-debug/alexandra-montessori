# Footer legal strip plan

## Intent and feel

The final expanded-footer strip should be quiet, current and dependable: copyright on one side, Privacy Policy and Contact on the other. It is small visually but legally and navigationally important, so the editor should offer precise structured controls rather than a rich-text box.

## Current evidence

- The strip appears only in expanded footer state, after the three-column region and a divider.
- `Footer.jsx` generates the current year at runtime and renders “© {year} Alexandra Montessori. All rights reserved.”
- Privacy Policy points to `/privacy`; Contact points to `/contact`.
- At small widths it stacks; at `sm` it becomes a row with links grouped at the right.
- Existing editable keys allow copyright and both link labels/hrefs, but a saved literal year could become stale.

## Editable elements and controls

- Copyright template with protected dynamic tokens `{year}` and `{brandName}`; edit surrounding phrase without hard-coding time-sensitive values.
- Ordered legal-link collection initially containing Privacy Policy and Contact: label, published-page reference, visibility and accessible-label override.
- Divider opacity, strip spacing, text size and alignment through footer token presets.
- Add a future legal page such as Terms only by selecting a published page; custom external legal destinations receive an explicit warning.
- Preview year rollover, long brand name, one/many links and 320px stacking.
- Reset each field to canonical brand/page source.

## Selection, layers and dragging

Copyright and link group are two selectable flow blocks. On desktop they occupy start/end slots; on mobile they stack in protected order. Link rows can reorder within the group, but free pixel movement/scaling is disabled. Dynamic tokens behave as protected inline chips during editing and cannot be accidentally partly deleted.

## Desktop, tablet and mobile

Desktop/sm-wide screens use a horizontal justify-between layout; narrow mobile stacks with centred alignment and sufficient link spacing. Text wraps naturally at zoom. The strip must remain inside expanded footer content and above the View less control. Cookie overlays must not obscure the only privacy link without a reachable duplicate in the cookie banner.

## Data ownership and bindings

Brand name comes from global Site Settings, year from runtime, and legal page targets from stable WordPress page references. The footer record stores template wording, link membership/order and presentation. The Privacy page owns legal copy; this strip owns only navigation to it.

## Protected rules

- `{year}` and `{brandName}` tokens remain valid; preview blocks malformed templates.
- At least one reachable Privacy Policy link must exist across footer/cookie surfaces.
- Unsafe protocols and unpublished target pages cannot publish silently.
- Keep semantic links and visible keyboard focus.
- Do not permit arbitrary scripts, HTML or shortcode in the copyright template.

## Accessibility and failure states

Small text must still meet contrast and zoom requirements. Link purpose is clear from label; duplicates remain consistent. If a bound legal page is missing, block the target change and retain the previous published link. If the copyright template is empty/invalid, use the runtime default. If all optional links are hidden, copyright still renders and layout recentres.

## Storage and versioning

Persist a tokenized copyright template and stable link records under `footer.legal`. Migrate current `footer-copyright`, `footer-privacy` and `footer-contact` values, converting a literal current year to `{year}` where safely detected. Revisions show legal destination changes prominently.

## Planned implementation files (future only)

- `src/components/footer/FooterLegalStrip.jsx`
- `src/lib/legalLinkModel.js`
- `wordpress/mu-plugins/alexandra-visual-builder/assets/panels/footer-legal.js`
- `wordpress/mu-plugins/alexandra-visual-builder/tests/footer-legal.php`
- `src/components/Footer.jsx`

## Current public section → exact WP canvas parity

| Public element | WP canvas requirement |
| --- | --- |
| Runtime copyright sentence | Same rendered sentence, with editable protected tokens |
| Privacy Policy → `/privacy` | Same initial label/route and hover/focus treatment |
| Contact → `/contact` | Same initial label/route and order |
| Stacked mobile / split row from `sm` | Same responsive placement and divider spacing |

## Acceptance checklist

- [ ] Initial copyright and two links match public output exactly.
- [ ] Year rolls over automatically without a client edit.
- [ ] Dynamic tokens cannot be broken by inline editing.
- [ ] Legal destinations use stable published-page references.
- [ ] Mobile/desktop alignment and focus states match the real footer.
- [ ] Missing/invalid data restores safe defaults and privacy remains reachable.
