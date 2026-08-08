# Home video hero plan

## Intent and feel

The Home opening should remain immersive and quiet: real nursery footage fills the space under the hanging logo, while a restrained dither/tint keeps the footage coherent with the pale-green brand. Editing should feel like art-directing a video crop, with safe fallbacks always visible.

## Current evidence

- `Home.jsx` renders `data-am-vb-region="home-hero"` at 540px desktop, 460px tablet and 380px mobile.
- Default video is `alexandra-promo.mp4` with `alexandra-promo.webm` fallback; a custom video replaces the MP4 and suppresses the built-in WebM source.
- It autoplays muted, loops, uses `playsInline`, preloads metadata and displays `alexandra-promo-poster.webp` while loading/unavailable.
- Video is full-bleed `object-cover`, brightened, followed by a dither texture at 35% and deep-green tint at 14%.
- The only heading is an `sr-only` H1 containing `brand.name`; no visible marketing copy overlays this public section.
- Existing v3 controls already cover video/poster, crop, per-device move/scale, height, tint, texture and visibility.

## Editable elements and controls

- Video upload/replace/library choice with MP4/WebM support matrix, duration, dimensions, file size and muted-audio guidance.
- Poster image upload/replace, alt/decorative purpose, focal x/y and “generate from video frame” future action.
- Per-device section height, video scale/offset and crop focal point; live safe-zone overlay accounts for hanging header logo.
- Tint strength 0–80%, texture strength 0–100% and brightness preset with current values marked as defaults.
- Autoplay/muted/loop/playsInline shown as protected behaviour, with an approved “poster only / reduced data” fallback mode if later required.
- Section visibility with serious page-opening warning and a hidden-state placeholder inside the editor.
- Reset video only, reset poster/crop only, or reset full hero.

## Selection, layers and dragging

Layer order is locked: video → texture → tint → accessible H1. Dragging the video changes the full media frame’s per-device optical offset; dragging inside Crop mode changes object position. Texture/tint can be selected through the layer list but never intercept pointer input. Poster is edited as the video fallback, not as a separate overlapping image layer.

## Desktop, tablet and mobile

Device tabs use exact current heights and independent move/scale values. Crop previews must cover 1440×540, tablet 768×460 and mobile 390×380/320×380. Video remains cover-fit without letterboxing; key faces/activities get per-device safe-position indicators. Reduced-motion/data saver preview shows the poster without a blank flash.

## Data ownership and bindings

Hero design belongs to the Home page model: section record plus typed `hero-video` and `hero-poster` media elements. WordPress media library owns binary assets and metadata. The screen-reader H1 references global brand identity and is code-protected. React fallbacks remain the current bundled MP4/WebM/poster if the WordPress payload is absent.

## Protected rules

- Video is decorative/background media: muted autoplay only; no surprise sound.
- Preserve one meaningful Home H1 even if the section is visually hidden/reworked.
- Clamp height, offsets, scale, tint and texture; prevent media from exposing an empty frame.
- Reject unsafe remote sources, unsupported codecs and excessively large unreviewed files.
- Texture/tint remain pointer-inert and cannot be reordered above interactive global UI.
- Replacing media never deletes the previous WordPress attachment.

## Accessibility and failure states

Reduced-motion preference should show poster or a non-animated first frame. The hidden H1 remains present and correctly named. If video cannot load, poster fills the exact frame; if poster also fails, use a deliberate sage/cream media fallback, not a broken icon. Upload validation explains codec/size failures before save. Editor interaction offers pause without changing published autoplay settings.

## Storage and versioning

Continue the versioned Home design document with stable media keys and attachment references. Store intrinsic metadata, fallback intent, focal point and per-device geometry; do not store media blobs. Revisions identify replacement versus crop/style edits. Any future source-set schema migrates current single custom video without losing poster/crop values.

## Planned implementation files (future only)

- `src/pages/Home.jsx` (`Hero` extraction/bindings)
- `src/components/home/HomeVideoHero.jsx`
- `src/lib/homeVisual.js`
- `wordpress/mu-plugins/alexandra-visual-builder/assets/panels/home-video-hero.js`
- `wordpress/mu-plugins/alexandra-visual-builder/tests/home-video-hero.php`

## Current public section → exact WP canvas parity

| Public hero layer/behaviour | WP canvas requirement |
| --- | --- |
| MP4 + built-in WebM, muted autoplay loop | Same real source/playback state in interaction preview |
| Poster while unavailable/loading | Same default poster and crop fills the stage |
| 540 / 460 / 380px heights | Exact desktop/tablet/mobile initial stage heights |
| Dither 35% above video, green tint 14% above dither | Same layer order, opacity and pointer-inert behaviour |
| Full-cover brightened video | Same object-cover/brightness and current centre crop |
| Screen-reader brand H1 | Same semantic H1 remains present though visually hidden |

## Acceptance checklist

- [ ] Untouched builder iframe and public hero are visually/behaviourally identical.
- [ ] Video, poster, crop, height, tint and texture can be edited/reset independently.
- [ ] Per-device crop keeps chosen subjects in safe zones.
- [ ] Reduced-motion/data/load failure always shows a composed fallback.
- [ ] H1, mute and protected layer rules cannot be broken.
- [ ] Media revisions retain previous attachments and fallback defaults.

