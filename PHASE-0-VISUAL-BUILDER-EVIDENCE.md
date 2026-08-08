# Phase 0 Visual Builder — Delivery Evidence

Date: 27 July 2026  
Environment: Local WordPress only  
Production deployment: none  
Existing public routes switched: none

## Outcome

The Phase 0 visual-editing proof of concept is installed in the Local Alexandra
Montessori WordPress site.

Open it from:

1. WordPress Admin.
2. Website Content.
3. Visual Builder.

The Website Content overview also contains a **Visual Builder — Prototype**
card.

The proof of concept edits a separate revisioned document named **Home page
prototype**. It does not edit the existing public Home route.

## Client editing workflow delivered

- Full-screen three-panel workspace:
  - Layers and Add controls on the left;
  - responsive page canvas in the centre;
  - contextual properties and saved versions on the right.
- Direct text editing on the canvas.
- Bold, italic, underline, bullet-list and numbered-list actions.
- Heading and paragraph/div text styles.
- Add Text, Button, Image and Shape elements.
- Rectangle, circle, pill, line and triangle shapes.
- Add and delete whole sections.
- Duplicate and delete sections or elements.
- Drag sections and elements in the Layers panel to reorder them.
- Move Up and Move Down controls as a precise alternative to dragging.
- Resize a selected element from its bottom-right corner.
- Exact width and height controls.
- Freeform layered Hero composition with X/Y position, z-index and rotation.
- Stack, two-column grid and freeform layered section layouts.
- Image replacement through the WordPress Media Library.
- Image crop mode and focal-point controls.
- Link URL and target controls for buttons.
- Desktop, Tablet and Mobile preview modes.
- Device-specific typography, size, spacing, position and appearance overrides.
- Background colour/image, padding, gap, radius and overflow controls.
- Border, radius, shadow, opacity, fill and text-colour controls.
- Undo, redo and keyboard shortcuts.
- Manual Save with an unsaved/saving/saved state.
- Protected Preview that uses the real React entry point.
- WordPress revisions with Restore.
- Recoverable deletion through Undo.

## Rendering contract

The editor canvas and React preview consume the same saved JSON block tree.
They also use the same shared `canvas.css` primitives. Responsive CSS is
generated from the saved Desktop, Tablet and Mobile values.

The preview is available only to a logged-in user with `edit_posts`. A request
without permission redirects to WordPress login. The document REST API also
requires that capability.

## Automated evidence

The following checks passed:

- PHP syntax validation for every plugin PHP file.
- JavaScript syntax validation for the editor.
- ESLint on the React preview integration.
- Vite WordPress production build: 1,943 modules transformed successfully.
- MU-plugin loaded by WordPress.
- Private `am_visual_page` record created.
- Revision support enabled.
- Seed document contains:
  - 2 sections;
  - 8 elements;
  - text, button, image and shape types;
  - Desktop, Tablet and Mobile values.
- Authenticated visual-builder admin page:
  - HTTP 200;
  - builder root present;
  - bootstrap configuration present;
  - editor JS and both CSS files present.
- All three builder assets return HTTP 200.
- REST read/save round-trip passes.
- Underline markup survives save.
- bullet-list markup survives save.
- an injected `<script>` tag is removed.
- camelCase responsive style keys survive save.
- revision creation passes.
- revision restore endpoint returns the expected earlier document.
- the clean original document is restored after the test.
- React preview:
  - HTTP 200 when authorised;
  - normal React root present;
  - protected builder JSON injected;
  - shared canvas CSS present;
  - Desktop, Tablet and Mobile CSS present;
  - built React bundle contains the preview component.
- Unauthenticated REST request returns HTTP 401.
- Unauthenticated preview request returns HTTP 302 to WordPress login.
- Existing public Home:
  - still returns HTTP 200;
  - still contains the normal React root;
  - contains no visual-builder draft payload;
  - contains no prototype CSS;
  - contains no prototype notice.
- Smoke test confirms no temporary test marker remains in the saved document.

## Rollback

- The pre-Phase-0 active Vite manifest is stored at:
  `alexandra-theme/dist-backup-phase0-manifest-20260727.json` in the Local
  theme.
- Previous hashed build assets remain in the Local theme.
- The proof-of-concept post type is private and isolated.
- Removing the Local MU-plugin loader disables the builder without changing
  the public content contract.

## Phase 0 limitations by design

These are not defects in the proof of concept; they belong to later approved
phases:

- only the Local Home prototype is enabled;
- there is no production deployment;
- existing Home, Nursery and other routes do not yet consume builder pages;
- no autosave or concurrent edit lock yet;
- no multi-select, grouping, layer lock or layer visibility yet;
- no snap guides or keyboard nudging yet;
- no reusable templates or synced sections yet;
- no full global Brand/Header/Menu/Footer editor yet;
- no dynamic collection-block editor yet;
- no publication readiness/audit workflow yet;
- no paste-cleanup workflow for Word/Google Docs yet;
- no broad accessibility/performance regression suite yet.

## Exit gate

Engineering gate: pass.  
Security and persistence gate: pass.  
React rendering-path gate: pass.  
Client visual/interaction approval: pending.

The correct next action is a short client-perspective walkthrough in normal
Chrome. Once the interaction model is approved, Phase 1 can harden this
prototype into the full builder foundation before page-by-page rollout.
