# Food & Hygiene — public-record action plan

## Intent and feel

Give parents a direct route to evidence when one exists and an honest update message when it does not. The editor must distinguish a branch-specific record from a generic ratings homepage.

## Exact current JSX and public evidence

- This is the footer area inside every existing rating card, not a separate public section.
- Current JSX tests only `rating.href` truthiness; it does **not** validate the URL or distinguish branch-specific from generic destinations.
- Truthy value renders external link `View public record` plus ExternalLink icon, `target="_blank"`, `rel="noopener noreferrer"`.
- Falsy value renders plain text `Public record details are being updated.`
- Hounslow and Heston have branch-specific FSA URLs. Hammersmith has truthy generic `https://ratings.food.gov.uk/`, so the **current public card also renders `View public record`** to that homepage despite its pending rating badge.

## Current public section -> exact WP canvas parity

| Current href state | Exact seeded WordPress footer parity |
| --- | --- |
| Hounslow branch URL | External `View public record` link with icon |
| Heston branch URL | External `View public record` link with icon |
| Hammersmith generic FSA homepage | Same current link label/icon and generic destination, plus editor warning |
| Empty/falsy URL | Plain `Public record details are being updated.` |
| Any current truthy malformed string | JSX would attempt a link; planned validation must block unsafe publication |
| Folder scope | Internal footer editor in the one card-grid section; no extra `<section>` |

## Exact editing controls

- Shared action label, pending sentence, icon, text/hover style, footer surface/border/padding and alignment.
- Bound URL preview with domain, destination classification (`Branch record`, `Authority/search homepage`, `Missing`), last checked/reviewer and safe test action.
- Source editing deep-link by nursery; page editor cannot replace a bound URL with static per-card copy.
- Optional destination-aware label pattern (for example a generic-page label) is an explicit future public wording change and remains off for exact seed parity.

## Layers, reorder and dragging

- Exact internal tree: `Rating card` → `Footer` → conditional `External action` → `Label`, `External-link icon` **or** `Pending message`.
- Footer remains after gradient body and pinned by card flex layout; it cannot be dragged above metadata or into a different branch card.
- Label/icon order is protected; template footer settings apply across bound instances.

## Responsive behaviour

- Preserve full-width white footer, px-6→sm:px-7 and py-5.
- Long labels/messages wrap without clipping, icon remains adjacent and link retains a visible focus ring.
- Footer follows the metadata in DOM at every breakpoint and stays at card bottom in equal-height desktop grid.

## Data ownership and override semantics

- `hygieneUrl`, destination classification, evidence/check data belong to each nursery Food Hygiene record.
- Shared label/message/icon/footer styles belong to the page’s rating-card template.
- A page label override does not change URLs; source URL edit shows every consumer and is versioned on the nursery entity.

## Protected behaviour

- New/changed destinations require parseable HTTPS where supported, allowed protocol/domain, safe target/rel and successful review/test.
- Generic homepage must be visibly classified in admin and cannot be claimed as a confirmed branch result without evidence.
- Invalid/unsafe URL never renders a dead or dangerous link; current truthy-only behaviour is evidence, not the planned validation rule.

## Accessibility

- Link purpose and external behaviour are available in text/accessibility name; icon is supplemental/decorative.
- Visible focus, 44px-safe target area, sufficient contrast and keyboard activation pass.
- Pending message is ordinary readable text and does not rely on badge colour.

## Empty, loading and error states

- Falsy/missing URL reproduces current pending sentence.
- Invalid-but-truthy source is caught in admin and resolves to safe pending state for a new publication; last valid published link can remain during transient test failure according to explicit policy.
- URL service/load failure is distinct from genuinely missing URL and shows stale/test-unavailable diagnostics.

## Storage and versioning

- Store footer template at `pages/food-hygiene/card-template/public-record-action`.
- Store URL, classification, source evidence, last test/check, reviewer and history at `nurseries/{uuid}/food-hygiene`.
- Diff highlights branch-specific↔generic↔missing transitions; rollback of template never rewrites source URLs.

## Planned implementation files (future only)

- `am-visual-builder/schema/pages/food-hygiene/public-record-action.php`
- `am-visual-builder/admin/pages/food-hygiene/PublicRecordActionEditor.jsx`
- `am-visual-builder/admin/components/ComplianceUrlInspector.jsx`
- `am-visual-builder/runtime/pages/food-hygiene/PublicRecordFooter.php`

## Acceptance checklist

- [ ] Hounslow/Heston branch links and Hammersmith’s current generic link reproduce exactly as seed.
- [ ] Documentation no longer calls the href check “valid URL” logic.
- [ ] Empty URL produces exact current update sentence.
- [ ] Generic/invalid/missing classifications, safe-link guard, focus and wrapping pass.
- [ ] Internal footer stays in the existing card; source/template diffs and rollback remain separate.
