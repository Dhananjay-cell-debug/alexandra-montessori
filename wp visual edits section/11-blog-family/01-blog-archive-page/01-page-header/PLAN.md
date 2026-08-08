# Blog archive — Page header plan

## 1. Intent and feel
Introduce the resource archive as calm, practical support for parents, with enough context to orient them before filters and articles begin.

## 2. Current React/public evidence and live-canvas parity
Blogs.jsx emits Seo then PageHeader with crumb="Blogs & Resources", eyebrow="Parent information", title="Blogs & resources" and an intro sentence. PageHeader.jsx currently accepts only title and intro, so the public section renders a centred h1 and intro on white; crumb and eyebrow are ignored. The live canvas must show exactly that output and not invent visible crumb/eyebrow layers.

## 3. Exact editable elements and controls
Expose h1 and intro text; safe inline emphasis in intro; heading/body typography, colours, max width, alignment and spacing; section background/padding/container; responsive overrides. Page settings expose SEO title, description and social image; canonical /blogs is locked. Keep ignored crumb/eyebrow props visible only as developer evidence, not editable public layers.

## 4. Layers and reorder rules
Layers: section > container > h1 > optional intro. Header stays first; h1 remains before intro. Intro may be hidden, but no new eyebrow/breadcrumb/media layers are supplied without a real JSX change.

## 5. Responsive behavior
Mirror PageHeader 4xl/5xl heading and base/lg intro defaults. Allow device padding/type/width; long intro wraps naturally and no fixed-height header is allowed.

## 6. Data ownership and binding
Header/SEO copy belongs to /blogs page-shell design. Article records, categories and dates do not bind here.

## 7. Protected functional logic
Preserve one h1, intro paragraph semantics, canonical route and React Seo output. Header edits cannot reset query filters or alter archive fetch behavior.

## 8. Accessibility
Enforce one h1, AA contrast, comfortable intro line length and semantic inline markup. Hidden intro is removed from accessibility tree rather than visually obscured.

## 9. Builder state previews
Preview normal, intro hidden, long copy, contrast warning and all devices. Archive data states are previewed in their actual downstream section.

## 10. Storage, publishing, and versioning
Store as blogs-header in revisioned /blogs design with sanitized text, responsive styles and SEO object. Draft, autosave recovery, published snapshot and base-hash conflict handling apply.

## 11. Planned implementation files (future only)
Add stable hooks/design reads to Blogs.jsx and PageHeader.jsx; define blogs-header in future class-am-vb-blog-design.php; add tailored inspector in assets/editor.js/canvas.css; test header, ignored props and SEO parity.

## 12. Acceptance checklist
- [ ] Canvas/live header render only current h1 and intro.
- [ ] No eyebrow or breadcrumb is invented.
- [ ] One h1 and contrast checks pass.
- [ ] Query/filter state is unaffected.
- [ ] Revisions restore copy and styles exactly.
