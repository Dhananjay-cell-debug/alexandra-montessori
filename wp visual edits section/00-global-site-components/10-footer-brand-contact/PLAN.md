# Footer brand and contact summary plan

## Intent and feel

The collapsed footer should be useful before anything is expanded: generous Alexandra branding on the left and a clean contact summary on the right. It should feel composed and trustworthy, with media editing tailored separately for the brand logo and official Ofsted mark.

## Current evidence

- `Footer.jsx` starts with a green background and a responsive top row: stacked/centred on small screens, left/right aligned at `lg`.
- The left `Logo` uses `footer-logo`, large badge plus editable two-line wordmark and strapline.
- The right column contains `brand.tagline`, general email, `brand.hours` and `footer-ofsted` media.
- Email uses `gmailHref` and opens a new tab; the Ofsted mark falls back to an inline official-style SVG when no upload exists.
- Existing v3 keys cover all visible copy and both media assets.

## Editable elements and controls

- Footer logo replace/upload, alt, contain sizing and bounded optical offset; edit brand line 1, line 2 and “Learning for life” strapline.
- Tagline multi-line text with character guidance; general email display text plus separately validated mail destination; opening-hours text.
- Top Ofsted mark replace/upload, alt, contain sizing and source/usage note.
- Footer background/text token, top-row padding, column gap and desktop alignment presets.
- Contact data may offer “sync from Site Settings” (recommended) or explicit display override, clearly labelled.
- Preview collapsed footer at short and long content lengths before entering disclosure controls.

## Selection, layers and dragging

Logo, copy group and Ofsted mark are selectable layers inside a protected two-column container. Desktop optical offsets are bounded; on smaller devices the stack order is fixed and centred. The email/hours lines can be reordered only within the contact group through named options, not freely dragged. Brand and official-mark media cannot overlap or leave the footer surface.

## Desktop, tablet and mobile

Desktop preserves left brand/right contact alignment. Tablet/mobile stack brand then contact with centred copy and responsive logo sizing. Device controls cover logo/mark size, gap and padding; content itself is shared. Test 320px width, long email, two-line hours and 200% zoom. The footer must not create a cream gap beneath short pages.

## Data ownership and bindings

General email and hours originate from `brand`/WordPress site settings; the footer record stores presentation overrides and optional display-copy override. Logo/Ofsted media belong to the global visual document with WordPress attachment references. The global brand token model supplies colours and typography. The footer plan must not own nursery-specific contacts.

## Protected rules

- Footer logo continues linking to Home and retains aspect ratio.
- Email destination must be a valid email/mail action; display text and target cannot silently diverge.
- Official mark uses contain fit and cannot be stretched/cropped into a misleading shape.
- Do not allow the collapsed summary to be entirely hidden.
- Text and focus contrast must remain valid against footer green.

## Accessibility and failure states

Logo link has a stable home accessible name. The email is readable and operable without requiring a Gmail account; implementation should prefer a normal `mailto:` unless a separately approved contact policy dictates otherwise. Uploaded Ofsted artwork needs meaningful alt; the fallback SVG already has an Ofsted label. Missing contact settings show a clear editor warning and omit only unavailable public fields, while defaults remain available during CMS-contract failure.

## Storage and versioning

Store footer presentation, text overrides and media references under the versioned global component. References to site settings are recorded as binding mode plus source key, not copied every save. Migrate current `footer-*` values from Home v3 into this global record with compatibility lookup until verified. Revisions must distinguish source-sync changes from explicit overrides.

## Planned implementation files (future only)

- `src/components/Footer.jsx`
- `src/components/Logo.jsx`
- `src/components/footer/FooterBrandContact.jsx`
- `wordpress/mu-plugins/alexandra-visual-builder/assets/panels/footer-brand-contact.js`
- `wordpress/mu-plugins/alexandra-visual-builder/tests/footer-brand-contact.php`

## Current public section → exact WP canvas parity

| Public element | WP canvas requirement |
| --- | --- |
| Large footer logo/wordmark at left on desktop | Same asset, lines, size, home link and responsive stack |
| Tagline, email and hours at right | Same source values, order, alignment and link behaviour |
| White-backed Ofsted mark | Same uploaded/fallback artwork, contain fit and spacing |
| Green footer surface | Same global colour/shadow relationship with no gap below |

## Acceptance checklist

- [ ] Collapsed footer canvas matches public desktop/tablet/mobile layout exactly by default.
- [ ] Logo, brand lines, tagline, email, hours and mark are independently selectable.
- [ ] Site-setting sync versus override is unmistakable.
- [ ] Long contact copy and missing fields do not break alignment.
- [ ] Media preserves aspect ratio and accessible naming.
- [ ] Footer changes are global, revisioned and page content remains untouched.
