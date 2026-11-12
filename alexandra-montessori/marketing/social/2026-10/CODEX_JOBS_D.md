# Alexandra Montessori - poster cut-out assets

You are producing cut-out assets for premium Instagram posters for Alexandra Montessori,
a Montessori nursery group in West London (babies to 5 years). Someone else does all layout
and typography. Your assets must contain NO text, letters, logos or watermarks.

## How to work
- Use your built-in `image_gen` tool, one call per job, highest quality, portrait or square
  as stated. Ask for a TRANSPARENT background and keep the alpha channel.
- If a transparent background is genuinely not possible, generate on a perfectly flat pure
  #00FF00 background with no green spill on the subject, then run
  `python "$CODEX_HOME/skills/.system/imagegen/scripts/remove_chroma_key.py"` (read its --help)
  to produce the transparent PNG.
- Copy each final PNG from `$CODEX_HOME/generated_images/...` into `./assets/` with the exact
  filename given. If the name exists, append `-v2`.
- Look at every result at full size. Regenerate once if: extra/missing fingers, distorted
  face or eyes, plastic/waxy skin, cropped limbs where the job says full body, halo or fringe
  around the cut-out, any text. Then move on.
- Do nothing else: no code, no other files.

## Style for all objects (one consistent set)
Soft matte 3D render, like premium clay/vinyl toys: rounded bevelled edges, subtle
subsurface softness, gentle studio light from top-left, soft ambient occlusion, no hard
outlines, no glossy plastic. Slight 3/4 angle. Each object alone, centred, filling about 80%
of a square 1024x1024 frame. Transparent background. Palette ONLY: laurel green #4f6b4c,
sage #a3bc9a, cream #f5efe6, natural beech wood #d9b98c, apricot #f0a979, butter #f2cf6b,
blush pink #f2b8b0.

## Jobs (filenames)
- D1 `icon-pink-tower.png` - Montessori pink tower: 6 graduated blush-pink cubes stacked.
- D2 `icon-golden-beads.png` - Montessori golden bead material: one bead bar of ten and three
  loose beads, butter-gold, on nothing.
- D3 `icon-globe.png` - small Montessori globe on a beech wooden stand, continents sage green,
  oceans cream-blue-ish pale sage.
- D4 `icon-watering-can.png` - small child's watering can, sage green.
- D5 `icon-jug-glass.png` - small glass jug and a little glass, water inside, beech tray.
- D6 `icon-laurel.png` - a single elegant laurel sprig with 9 leaves, laurel and sage greens.
- D7 `icon-apple.png` - an apricot-red apple with one leaf, cut half beside it.
- D8 `icon-rainbow-stacker.png` - wooden arched rainbow stacker in apricot, butter, sage,
  laurel and blush arches.
- D9 `icon-clock.png` - a round wall clock, cream face, laurel rim, two hands at 8 o'clock,
  small dots instead of numbers.
- D10 `icon-pencil-paper.png` - a chunky beech triangular pencil resting on a cream sheet
  with a wavy sage line drawn on it.
- D11 `icon-heart-hands.png` - a soft blush heart cradled in two small rounded cartoon-clay
  hands (simple, cute, no faces).
- D12 `icon-location-pin.png` - a rounded map pin in laurel green with a cream centre.
