# Home page — section-plan index

## Scope status — existing implementation, locked reference

The Home visual editor is already substantially implemented. This folder is a documentation baseline for the interaction quality, control depth and live-canvas parity that the remaining page workstreams must meet. It is **not** authorization to rebuild, replace or modify the current Home React page, WordPress visual builder, saved Home data or production output. Any future Home implementation change requires a separate explicit instruction.

This folder plans the real `/` page section by section. It is grounded in `src/pages/Home.jsx` and the current WordPress visual-builder v3 contract. Global header, footer, social rail and cookie controls belong to `../00-global-site-components/` and are referenced, never redefined, here.

| Order | Section plan | Live ownership |
| --- | --- | --- |
| 01 | `01-page-seo-social-preview` | `<Seo>` title/description/path/share image and search preview |
| 02 | `02-video-hero` | Hero video, poster, crop, tint, texture and responsive height |
| 03 | `03-nursery-feature-links` | Feature heading/photo/link/icon collection |
| 04 | `04-montessori-benefits` | Benefit icon-and-copy collection |
| 05 | `05-about-journey-story` | About heading, three story paragraphs and founders image |
| 06 | `06-about-milestone-chips` | Established and optional upcoming milestone chips |
| 07 | `07-testimonials-stage` | Background image, overlay, section heading and archive CTA |
| 08 | `08-testimonial-cards` | Up to three testimonial cards and card-layer controls |
| 09 | `09-trust-accreditations` | Trust/accreditation label-and-logo collection |
| 10 | `10-custom-inserted-home-sections` | Existing CustomHomeSections runtime, template selection, anchors, visibility, height and owned-layer summary |
| 11 | `11-custom-section-layer-canvas` | Text/image/logo/button/shape/tab layers and direct manipulation |
| 12 | `12-home-section-outline-order` | Canonical section outline, selection, protected ordering and custom placement |

The Home page currently renders in this protected semantic order: video hero → custom blocks after hero → feature links → custom blocks → benefits → custom blocks → about → custom blocks → testimonials → custom blocks → trust → custom blocks. The plans below preserve this evidence unless a later implementation deliberately introduces a migration-safe ordering model.
