# Handoff — Alexandra Visual Builder, next session

> **Newest governing audit:** read
> [CLAUDE-VISUAL-BUILDER-RELENTLESS-PARITY-AUDIT.md](../CLAUDE-VISUAL-BUILDER-RELENTLESS-PARITY-AUDIT.md)
> before continuing implementation. It contains the 1 August rendered-route measurements,
> systemic blockers, page-by-page completion list, and hard acceptance gates. The historical
> notes below remain useful, but appearance controls alone do not satisfy the new parity gate.

Read this before `00-MASTER-IMPLEMENTATION-STATUS.md` (that file predates the engine) and ignore
`RESUME-HERE.md` in the project root entirely — it is from 18 July and describes different work.

> ## Update, 1 August 2026 (evening) — plugin `0.9.15`
>
> ### The bug behind almost everything, found and fixed
>
> **Every edit destroyed the edit before it, on every page that had no saved design yet.** Move one
> slider, move a second, and the first snapped back to zero — in the panel, in the canvas, and in
> the save payload.
>
> Root cause, proven in the running builder, not inferred:
>
> - PHP encodes an empty associative array as JSON `[]`, so `design.elements` and
>   `design.collections` arrived in JavaScript as **arrays**.
> - `updateHomeDesign` then wrote `design.elements['some-key'] = {...}` — a *named property on an
>   array*.
> - `clone()` is `JSON.parse(JSON.stringify(...))`, and **`JSON.stringify` discards named properties
>   on an array**. So the next edit cloned the model and every earlier property vanished.
>
> Measured before the fix: the model held exactly `{"borderWidth":3}` after setting font size,
> corner radius *and* border width. After: `{desktop:{fontSize:48},…,radius:18,borderWidth:4}`.
>
> Fix is `toMap()` / `normaliseDesign()` at the top of `editor.js`, applied at every point a design
> enters the model — `applyDesignPayload`, before every `clone()` in `updateHomeDesign`, and on save.
> `saveHomeDesign` now also posts `homeDesignRef.current` rather than the render closure, and
> re-syncs the ref from the server response (it previously went stale after every save).
>
> **This is why "other pages restrict us" — they were not restricted, they were losing the data.**
> The earlier "per-device buckets wipe each other" symptom was the same bug, not a second one.
>
> ### Also shipped
>
> - **Copy / Paste / Duplicate / Remove** in the inspector for every selection, plus **Ctrl+C**,
>   **Ctrl+V**, **Ctrl+Shift+V** (paste appearance only) and **Ctrl+D**. Shortcuts are bound in the
>   canvas iframe too, and stand down when the client is typing or has a text selection.
>   Each button routes to the mechanism that genuinely exists for that kind of element: free layers
>   duplicate in place, collection items clone their whole element set, and a fixed page element is
>   copied into a free layer inside a shared "Copied items" band anchored to its region (repeat
>   copies reuse that band instead of spawning one strip per copy).
> - **Remove** on a fixed element sets `hidden` on all three breakpoints — the markup belongs to
>   React, so hiding is what removal honestly means. Watch for the trap this created and its fix:
>   the element then vanished **from the canvas as well**, and since the only way to bring it back
>   is to select it, the client was stranded. `style-vars.css` now renders a removed element ghosted
>   (30% opacity, dashed outline) **inside the builder while editing only**, scoped by
>   `html[data-am-vb-canvas-mode="edit"]`. Verified it still goes fully to `display:none` in View.
> - **About, Testimonials, Privacy and the 404 are registered** (23 → 27 routes), `SEED_VERSION`
>   bumped to 2 so their documents seed. Seeding skips routes that already have one.
>
> ### Verified this session, in the running builder
>
> - Three properties set in sequence on Careers/Apply all coexist in model, panel and canvas.
> - Per-device values coexist: `{desktop:{fontSize:30}, tablet:{fontSize:12}}`, switching back shows 30.
> - Full save round trip on **About**, a page with no prior design: fontSize 44 on three
>   breakpoints, radius 22, borderWidth 5, opacity 70 all persisted server-side. `elements` came
>   back as an object, not an array.
> - Copy → Duplicate → Ctrl+V twice: 3 layers, 1 host section, all rendering in the canvas.
> - Remove → ghosted and selectable → Restore → back to `display:block`.
> - All 27 `page-design/*` endpoints 200. All public routes 200, no fatals. Home unregressed.
> - All PHP lints clean, all JS `node --check` clean, Local copy and repo mirror byte-identical.
>
> ### Two things to know
>
> 1. **Another agent (Codex) was editing this plugin at the same time and silently reverted
>    `editor.js` mid-session.** Check `ls -la` timestamps on `assets/` before you trust that your
>    edits are still there.
> 2. **Do not POST to `page/<slug>/lock` as a probe.** It steals the lock, the editor goes read-only,
>    and the inspector's action row disappears with no visible explanation. It expires after 120s.
>
> ### Next
>
> **Annotation is the remaining half of "as sophisticated as Home."** Non-Home elements are still
> discovered heuristically: keys look like `about-about-header-text-1` and names like "Layout card
> 19". A positional key *moves when a paragraph is added above it*, silently reassigning a saved
> edit to the wrong element — so this is a correctness problem, not only a cosmetic one. Annotate
> with `editable()` from `src/lib/pageVisual.js`, pattern in `Curriculum.jsx`, then `npm run
> build:wp` and copy `dist` into the Local theme. Current counts: Home 19, Availability 13,
> NurseryDetail 11, FeeCalculator 8, Careers 6, BlogArticle 6, long tail after that;
> About/Testimonials/Privacy/NotFound have none.

> ## Update, 1 August 2026 (later session) — plugin `0.9.8`
>
> **The "public-site parity" item below was not merely unverified. It was broken, and it is now
> fixed.** Everything from "The one idea that matters" down is still accurate as history; the
> corrections are here.
>
> ### What was actually wrong
>
> The four-layer rule was right, and two layers were failing:
>
> | Layer | Home, before | Other 22 pages, before |
> | --- | --- | --- |
> | 1. Inspector panel | schema ✅ | schema ✅ |
> | 2. Canvas applier | schema ✅ | schema ✅ |
> | 3. **Server save** | schema ✅ | ❌ **dropped every schema property** |
> | 4. **Public front end** | ❌ **rendered none of them** | ❌ **rendered none of them** |
>
> - `AM_VB_Site_Design::sanitize()` built each element from a fixed nine-key array and never called
>   the schema. Measured: **14 of 14** properties a client set on a Curriculum heading were silently
>   discarded on save. A client could style anything, watch it work on the canvas, press Save, and
>   lose it. That is the "other pages restrict us" problem, exactly.
> - `homeVisual.js` (React) and `site-runtime.js` both emitted **only** the three legacy transform
>   variables. So even on Home, rotate/width/radius/border/shadow/hover/animation/colour reached the
>   browser as data and were never rendered on the real page.
>
> ### What changed
>
> - `class-am-vb-site-design.php` now calls `sanitize_flat`/`sanitize_device`. Non-Home saves keep
>   **exactly** the same keys Home keeps — measured, 0 dropped.
> - `style-apply.js` gained `applyDesign(model, root)` and runs it on the public page for
>   `window.amHomeDesign`; `site-runtime.js` calls `applyToNode` while annotating. Layer 4 is now the
>   same shared applier everywhere, so no property can render in the canvas and vanish on the site.
>   It skips itself inside the builder canvas (`?am_visual_canvas=`), where the editor owns the DOM.
> - **Sections are now a schema type.** The `if (region.id === 'home-hero')` chain is gone.
>   `packs()` has a `section` pack; `props_for_type('section')` resolves it exactly like an element.
>   Bands went from 5 hardcoded controls to **13 on every page, 14–16 on Home**, and they gained
>   radius, border, shadow, hover, opacity and scroll animation that no page had before.
>   Region-specific properties are gated by `sections` / `notSections` metadata — enforced in the
>   panel **and** at save, so an off-region value cannot even be stored.
> - **Custom free layers** now get the schema toolkit via `renderSchemaStyles` with a `skip` map, and
>   `shape` layers no longer fall through the server untouched (they were dropping everything).
> - **Annotations:** new helper `src/lib/pageVisual.js`. `PageHeader.jsx` is annotated once and
>   covers the H1 + intro on **17 pages**; `Curriculum.jsx` is fully annotated as the reference.
>
> ### Two traps found the hard way
>
> 1. **There is a second copy of the whole plugin** at
>    `alexandra-montessori/wordpress/mu-plugins/alexandra-visual-builder/`. It was frozen at 29 July
>    and did not contain the style engine at all — no `class-am-vb-style-schema.php`, no
>    `inspector.js`, no `style-apply.js`. It is the version-controlled copy, so a deploy from it
>    would have silently shipped a build with none of this work. **It is now synced byte-for-byte.
>    Re-sync it whenever you touch the Local copy**, and note `npm run test:visual-regions` reads its
>    registry, not Local's.
> 2. Adding controls to a band is not enough — check the band's markup consumes them. `.am-home-hero`
>    forces `padding-block: 0` and has no `.am-home-content` / `.am-home-grid`, so vertical spacing,
>    internal spacing and content width are genuinely inert there. They are gated with
>    `notSections: ['home-hero']` rather than left as decoration.
>
> ### Verified this session (all runnable, all passing)
>
> - `24/24` section + element round-trip through **both** servers, including hostile input
>   (`radius 99999 → 200`, `shadow "url(javascript:alert(1))" → none`, off-region values rejected).
> - `22/22` custom free layers keep their schema properties, shapes included.
> - `37/37` every schema CSS variable and data-attribute has a consuming rule in `style-vars.css`,
>   and the applier is wired into the canvas, the public page and the site runtime.
> - Element property counts unchanged: text 22, image 24, frame 17 — nothing regressed.
> - All PHP lints clean, all JS `node --check` clean, React build + sync clean, eslint clean,
>   visual-region contract passes (100 anchored regions), **16/16 routes return 200, no fatals**.
>
> ### Still NOT verified — do not take on trust
>
> - **Handle dragging by hand.** Still unproven. I did find the likely reason automated drags fail:
>   `editor.js` bails out of the move when `pointerType === 'mouse' && buttons === 0`, and a
>   synthetic `pointermove` defaults `buttons` to `0`. That points to a harness artefact rather than
>   a real bug, but it is not proof. **Drag all eight handles plus the rotate stalk yourself** on a
>   heading, a photo and a card.
> - **A saved design seen on the real page with human eyes.** The render chain is now verified
>   mechanically end to end, but nobody has yet saved a design and looked at the live page. The
>   Chrome extension could not attach to `alexandra-montessori.local` (no host permission granted),
>   so this needs a person or that permission.
> - One known limitation: if React re-renders a node after the applier has run, it will overwrite the
>   inline custom properties. The existing runtime has always had this exposure; it is mitigated by
>   re-running on `load` and after 600ms, not eliminated.
>
> ### Next, in order
>
> 1. Hand-verify the handles and one saved design on the real page (above).
> 2. Annotate the remaining page bodies with `pageVisual.js` — see the pattern in `Curriculum.jsx`.
>    Key format `<region>-<thing>`; never end a key `-<type>-<number>`, which is what the runtime
>    generates for unnamed nodes.
> 3. `hardProtected` elements still show a message and no controls. That is a deliberate policy for
>    global header/footer components — **ask before changing it.**
> 4. `AM_VB_Plugin::enabled()` still gates the builder to local/dev hosts, so **none of this reaches
>    alexandramontessori.co.uk.** The production story is still an open question.

---

## The one idea that matters

Home was never "better designed" than the other pages. It was **hardcoded**.

`editor.js` contained a chain of `if (region.id === 'home-hero')`, `'home-testimonials'`, and so on —
six bespoke blocks giving Home controls nobody ever wrote for the other 22 surfaces, which all fell
through to a single five-control generic fallback. Curriculum, Fees, Blog and Careers got byte-identical
panels because nothing in the code knew what those sections were.

**Therefore: never add another per-page or per-element-type branch.** Every control must be declared once
as data and rendered generically. If you find yourself writing `if (type === 'image')` in the panel, stop —
that is the exact disease this session removed.

---

## What exists now

| File | Role |
| --- | --- |
| `includes/class-am-vb-style-schema.php` | **Single source of truth.** Property definitions: label, group, control, range, default, per-device flag, CSS variable or data attribute. |
| `assets/style-apply.js` | One applier used by **both** the editor canvas and the public front end, so a property cannot render in one and vanish in the other. |
| `assets/style-vars.css` | The render layer. Breakpoints are 640px / 1024px, matched to the React app's `src/index.css`. |
| `assets/inspector.js` | The panel: max three tabs, one group open, others collapsed behind a value summary. Zero element-type branching. |
| `assets/editor.js` | Hardcoded slider blocks removed; calls the schema panel. Canvas handles live here. |
| `includes/class-am-vb-home-design.php` | Save whitelist now runs schema sanitisation alongside the legacy clamps. |

**Adding a new editable property is now one entry in the PHP schema.** It then appears in the inspector,
survives the save whitelist, renders in the canvas, and renders on the public page — automatically, on
every page that consumes the schema. That is the whole point. Protect it.

### The four layers

A property must survive all four or it silently dies. This is why the schema exists:

1. Inspector panel (`inspector.js`)
2. Canvas applier (`editor.js` → `applyHomeElementToFrame` → shared applier)
3. **Server sanitiser** (`class-am-vb-home-design.php`) — a hard whitelist; anything unknown is dropped
4. Public runtime (`style-apply.js` on the front end)

### Property set

Universal base (all 16, every element, no exceptions): move X/Y, size, **rotate**, width, height,
layer order, padding, background fill, corner radius, border width/colour/style, opacity, shadow,
hover effect, scroll animation, hide.
Text pack: size, weight, line height, letter spacing, colour, alignment, capitalisation.
Media pack: fill/fit, crop shape, mask, brightness, saturation, tint + strength.

### Canvas handles

Eight handles plus a green rotate stalk. Corners scale proportionally, edges stretch one axis
(`width` / `height`), stalk rotates with Shift snapping to 15°. The text-vs-image difference is **not**
special-cased: setting `width` makes a heading rewrap and makes a photo stretch, because that is simply
how the two lay out. Keep it that way.

---

## Verified this session

- All PHP lints clean; all JS passes `node --check`; site returns 200 with no fatal.
- 14/14 sanitiser checks pass, including hostile input: `radius: 99999 → 200`,
  `shadow: "url(javascript:alert(1))" → none`, `background: 'red; content:"x"' → ""`.
- Rotate driven from the panel visibly rotated a photo in the canvas — full chain confirmed.
- Numeric spin-boxes appear on every slider, accepting two decimals.

## NOT verified — do not take on trust

- **Handle dragging.** Synthetic drags in the automation harness produced no width change. Probably a
  harness limitation (the code relies on `setPointerCapture` and a 5px threshold), but it could be a real
  bug. **Drag each handle by hand first**, on text, image and card.
- **Public-site parity.** Assets load and are served, but no styled element has ever been saved and then
  viewed on the real page. Save one and look.

---

## Control audit (done 1 Aug, v0.9.4) — bugs found and fixed

Four controls were doing nothing. Worth knowing the *shapes* of these failures, because the
same traps apply to every property added from here:

1. **Scroll animation was completely dead.** `observeAnimations()` existed but was never called,
   so `data-am-vb-inview` was never set and the CSS never fired. Now auto-runs on DOM ready, on
   `load`, and once more after 600ms (the React app paints late). In edit mode elements are forced
   to `inview="true"` so a waiting element never sits invisible on the canvas.
2. **Width / height / padding / radius / border / rotate silently ignored on inline elements.**
   CSS does not apply them to a non-replaced inline box — links and spans. Only genuinely inline
   nodes are now promoted to `inline-block`.
3. **Layer order was inert** on statically positioned boxes. Fixed in JS, conditional on computed
   `position: static`. It was briefly a blanket CSS rule, which **un-fixed the social rail** —
   never force `position` from CSS here.
4. **Font weight lied about its state.** Its default `''` was not among its options, so the select
   displayed "300" while the value was actually unset. Added an explicit `automatic` option.

**Lesson for new properties:** a property is not done when it appears in the panel. Check that it
(a) is actually written to the DOM, (b) is not ignored by CSS for that element's display or
position, (c) has a default that exists among its own options, and (d) has something that actually
invokes it if it needs JS.

**Still cosmetic, not fixed:** colour inputs show `#ffffff` when unset, because `<input type="color">`
has no empty state. Low priority, but it misrepresents "no colour chosen".

## Per-type applicability (v0.9.5) — the rule that supersedes "give everything to everyone"

An inert control costs more trust than a missing one. Property definitions now carry `types`
(allowlist) or `notTypes` (blocklist), and `AM_VB_Style_Schema::applicable()` filters them — enforced
in **both** the inspector and the save whitelist, so an inapplicable value cannot even be stored.

Decisions made, and why:

- **Layer order deleted outright.** `z-index` is inert on a static box, and the one CSS fix that made
  it work globally would have un-fixed the social rail. It only ever mattered for free-placed layers,
  which use a different panel. Removed rather than left as decoration.
- **Padding and height removed from text.** Padding inflates the box and reads as a bug; a forced
  height just parks empty space under the words.
- **Text gets "Space between lines" and "Space between letters" in the Spacing group instead.** Same
  group name, type-appropriate meaning: for a box, spacing is padding; for words, it is leading and
  tracking. This is the pattern to follow for future type differences.
- **Text loses its north/south resize handles** to match — a handle that cannot change anything is
  worse than no handle. Handle sets are chosen per type in `highlightLiveElement`.

Verified counts: text 22 props, image 24, frame 17.

**Apply this rule to everything you add.** Before shipping a control, name the element types it can
actually affect and gate it. If the answer is "none of them, really" — delete it.

## Known gaps, in priority order

1. **Section panels are still the hardcoded `if (region.id === ...)` chain.** Same disease, one level up.
   Convert sections to the schema exactly as elements were. Biggest remaining win.
2. **Custom free-layer panel** (`renderCustomHomeElementFields`) still uses its own hardcoded controls and
   a different update path (`updateCustomItem`, not `updateHomeElementValue`). It does not get the base
   toolkit.
3. **`hardProtected` elements are a true dead end** — the panel shows only a message, no controls. These are
   global header/footer components where page-level overrides are deliberately disabled. Changing that is a
   policy decision; **ask the user before touching it.**
4. **Other pages have almost no annotated elements.** `Curriculum.jsx` has *one* `data-am-vb` attribute and
   it is a section wrapper — not a single clickable heading. Home has 19. The engine is ready for those
   pages; the annotations are not there. On non-Home pages elements are discovered heuristically from the
   DOM, which is why you get meaningless names like "Layout card 1".
5. **Align & snap** was approved but not built — it is a canvas drag interaction, not a stored property.

---

## How to work here

- **Local WP must be running.** Site is `https://alexandra-montessori.local`. Local's app is at
  `C:\Users\Dhananjay\OneDrive\Desktop\DHANANJAY\Local\Local.exe`.
- **Lint PHP** with Local's bundled binary:
  `C:\Users\Dhananjay\AppData\Roaming\Local\lightning-services\php-8.2.29+0\bin\win32\php.exe -l <file>`
  Its CLI has **no mysqli**, so you cannot boot WordPress from the command line. Test the sanitiser
  standalone by defining `ABSPATH` and stubbing `sanitize_hex_color`.
- **Always confirm the site returns 200 after touching PHP.** This mu-plugin has caused a site-wide fatal
  before, and there are two files named `alexandra-visual-builder.php` (an outer loader and an inner one) —
  the same inner/outer trap that broke `alexandra-operations`.
- **Bump `AM_VB_VERSION`** on every asset change or the browser serves stale JS.
- **Test in a separate browser tab.** Do not disturb the tab the user is working in.
- Unsaved builder changes are discarded by reloading — useful for cleaning up after experiments.

## Rules from the user, learned the hard way

- **Make only the exact change asked.** No restructuring, no bonus features. Ask before restructuring.
- **Never deploy without review.** The builder is local-only anyway (see below).
- **Do not slow-walk with questionnaires.** Bring findings and a recommendation, not a menu of options.
- **Do not claim completion you have not verified.** State plainly what is confirmed and what is not.
- **The panel must stay calm.** Elegant, minimal, carefully structured — a client should never face a wall
  of sliders. Max three tabs, one group open, collapsed groups show their current value.

## Production reality

`AM_VB_Plugin::enabled()` gates the entire builder to local/dev/`.local` hosts. **It does not run on
alexandramontessori.co.uk at all**, so nothing the client edits in the builder currently reaches the live
site. A production story is still an open question — raise it, do not silently assume it.

Backups of the pre-session assets are in `_backup-20260801-123740/` inside the plugin directory.
