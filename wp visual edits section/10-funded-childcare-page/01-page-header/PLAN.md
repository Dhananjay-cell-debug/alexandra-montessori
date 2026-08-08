# Funded Childcare — page header plan

## Intent and feel

Open with a calm, centred promise and one short explanation. The editor should feel deliberately simple because the current public header contains only a title and introduction.

## Exact current JSX and public evidence

- `FundedChildcare.jsx` passes `crumb="Funded Childcare"` and `eyebrow="Parent information"` to `PageHeader`, but `PageHeader.jsx` destructures and renders only `title` and `intro`.
- Therefore **no breadcrumb and no eyebrow are visible in the current public header**.
- Visible h1: `Quality early years education, with government support`.
- Visible intro: `We accept government-funded childcare across all three nurseries. Here's a simple guide to the funding on offer, who's eligible and how to claim your hours.`
- The actual section is white with `pb-6 pt-12 sm:pt-16`; title is centred 4xl→5xl and the intro is centred below at max-w-3xl.

## Current public section -> exact WP canvas parity

| Current JSX/public layer | Exact seeded WordPress canvas layer |
| --- | --- |
| One white `PageHeader` section | One `Page header` section in the same first visible position |
| Centred h1 | `Page title` layer with exact current text and h1 semantics |
| Conditional intro paragraph | `Introduction` layer with exact current text |
| Passed but ignored `crumb` prop | Source diagnostic only; **not** a visible breadcrumb layer |
| Passed but ignored `eyebrow` prop | Source diagnostic only; **not** a visible eyebrow layer |

## Exact editing controls

- Title and introduction direct-text editors; approved rich text is not needed for the h1 and is limited to safe inline emphasis/links in intro.
- Section background token, top/bottom spacing presets, content width, alignment, title scale/colour and intro scale/colour/measure.
- Optional `{nursery_count}` token insertion for the current phrase `all three nurseries`, with live resolved preview and explicit literal/token mode.
- A separate `Proposed additions` action may plan a breadcrumb/eyebrow later, but it must be labelled as a public design change and remain off by default.

## Layers, reorder and dragging

- Exact tree: `Page header` → `Page title`, `Introduction`.
- Title stays first and cannot be removed, demoted or dragged below the intro; intro can be selected directly but not moved outside this section.
- Section remains first among visible page content. No hidden crumb/eyebrow layers are inserted to satisfy unused props.

## Responsive behaviour

- Preserve current `pt-12`→`sm:pt-16`, 4xl→5xl title and natural title/intro wrapping.
- Mobile keeps centred alignment and readable measure with no horizontal overflow; device-specific typography uses approved presets only.
- Factual intro cannot be hidden on one device while remaining on another.

## Data ownership and override semantics

- Header content/style belongs to `page:funded-childcare.sections.header`.
- The nursery count token, if deliberately selected, resolves from published nursery records; literal seed remains exact current copy until changed.
- `crumb` and `eyebrow` passed by JSX are unused source props, not record-owned public content and not imported into the canvas.

## Protected behaviour

- Exactly one visible h1; no raw HTML, scripts, arbitrary classes or free-form route editing.
- Eligibility/support claims and nursery-count assertions receive freshness/reviewer warnings.
- Adding breadcrumb or eyebrow requires a clearly reviewed public-structure revision, not a silent parity migration.

## Accessibility

- Maintain h1 semantics and logical title-then-intro DOM order.
- Colour contrast, 200% zoom, long-word wrapping and inspector keyboard controls must pass.
- No empty navigational landmark is created for the ignored breadcrumb prop.

## Empty, loading and error states

- Empty title blocks publication; recovery offers current h1 or WordPress page title without silently publishing an empty h1.
- Empty intro follows current component behaviour and omits the paragraph cleanly, but the editor warns about lost context.
- Failed nursery-count resolution shows last published literal/resolved copy and a stale-data warning, not `undefined`.

## Storage and versioning

- Store `pages/funded-childcare/sections/header` with title, intro AST, token mode, style tokens, responsive overrides and source-prop diagnostic snapshot.
- Revisions show literal↔token changes and record count used at publication.
- Section-only restore must not affect SEO or offerings.

## Planned implementation files (future only)

- `am-visual-builder/schema/pages/funded-childcare/header.php`
- `am-visual-builder/admin/pages/funded-childcare/HeaderEditor.jsx`
- `am-visual-builder/admin/components/UnusedPropDiagnostic.jsx`
- `am-visual-builder/runtime/pages/funded-childcare/PageHeaderSection.php`

## Acceptance checklist

- [ ] Seeded output contains only the current h1 and intro—no breadcrumb or eyebrow.
- [ ] Current white spacing, centred measure and responsive type reproduce.
- [ ] One-h1 protection, direct editing, token/literal mode and review warnings work.
- [ ] Missing intro/title and failed count states behave safely.
- [ ] Keyboard, contrast, zoom, revision diff and isolated restore pass.
