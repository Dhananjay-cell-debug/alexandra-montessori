# Global site components — section-plan index

This folder contains planning only. Every child folder owns one visible or functional global surface; the plans must be implemented independently and then integrated through a shared versioned global model. Page-specific content must not be added here.

| Order | Section plan | Scope boundary |
| --- | --- | --- |
| 01 | `01-brand-design-tokens` | Approved colours, type, spacing, radii, shadows, motion and responsive primitives |
| 02 | `02-site-shell-landmarks` | App shell, skip link, main landmark and short-page/footer behaviour |
| 03 | `03-desktop-header-brand` | Desktop header bar and hanging logo only |
| 04 | `04-desktop-primary-navigation` | Six primary links, labels and active/hover treatment |
| 05 | `05-nursery-dropdown` | Nursery flyout and its dynamically sourced nursery links |
| 06 | `06-parent-information-menu` | Desktop hamburger and Parent Info resource panel |
| 07 | `07-mobile-navigation` | Mobile menu, nested nursery list and Parent Info second view |
| 08 | `08-availability-cta` | Global “Check availability” action in desktop/mobile navigation |
| 09 | `09-social-links-rail` | Fixed social links rail and platform records |
| 10 | `10-footer-brand-contact` | Footer logo, strapline, email, opening hours and top Ofsted mark |
| 11 | `11-footer-disclosure-control` | View more / View less progressive disclosure behaviour |
| 12 | `12-footer-quick-links` | Parent-information quick-link column |
| 13 | `13-footer-nursery-contacts` | Per-nursery name, address and phone records |
| 14 | `14-footer-accreditation-reports` | Official Ofsted report column and pending states |
| 15 | `15-footer-legal-strip` | Copyright, Privacy and Contact strip |
| 16 | `16-cookie-consent-banner` | First-visit consent notice and primary choices |
| 17 | `17-cookie-preferences-dialog` | Category-level consent dialog and persistence |
| 18 | `18-global-cta-pattern` | Reusable safe CTA/link editing contract across pages |
| 19 | `19-modal-layer-accessibility` | Shared overlays, stacking, focus, scroll lock and editor action safety |
| 20 | `20-new-page-creation-flow` | Draft-first creation and hand-off into a real future visual page |
| 21 | `21-page-section-template-picker` | Select, preview and insert tested sections into a future page |
| 22 | `22-menu-tree-and-nested-page-placement` | Place future pages in primary, Parent Info or nested dropdown/menu trees |
| 23 | `23-slug-unpublish-delete-redirect-safety` | Safe route rename, unpublish, trash, restore and redirect handling |

Global header, footer, social rail and cookie controls are referenced by page plans rather than duplicated inside each page family. “Global” means one saved record affects every page using that component; the builder must say this before save.

Plans 20–23 are global authoring workflows for future pages. They do not create a thirteenth current page workstream and do not change any current public section, route or menu until a client explicitly creates, completes, places and publishes a new page.
