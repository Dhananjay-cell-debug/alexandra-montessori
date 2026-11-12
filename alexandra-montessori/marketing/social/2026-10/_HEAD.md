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
