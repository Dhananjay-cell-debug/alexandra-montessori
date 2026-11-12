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

## Style for all people
High-end commercial children's studio photography (think premium UK children's fashion
catalogue): soft large key light from the front-left, gentle fill, real skin texture, natural
hair with fine flyaways, genuine (not posed-cheesy) expression, sharp eyes. Clothing is
simple, good-quality knitwear and cotton in Alexandra's palette: sage green, laurel green,
oat, cream, soft rust, butter yellow. No logos, no prints with text, no school uniform.

## Job C1 - `kid-girl-3-pink-cube.png` (portrait 1024x1536)
A 3-year-old British South Asian girl, dark hair in two soft puffs, sage-green chunky knit
cardigan over a cream top. Waist-up, body turned slightly right, laughing with delight and
looking up and to the right. She holds one small pale-pink wooden cube (from a Montessori
pink tower) up near her chin with both hands. Transparent background.

## Job C2 - `kid-boy-4-pointing.png` (portrait 1024x1536)
A 4-year-old Black British boy, short tight curls, oat-coloured crew-neck jumper, navy
cord trousers, sitting cross-legged on the floor, full body including socks/shoes,
excited open-mouth smile, raising his right index finger up as if he just had an idea,
eyes looking up at it. Transparent background, no floor, soft contact shadow allowed.

## Job C3 - `kid-toddler-2-watering-can.png` (portrait 1024x1536)
A 2-year-old white British toddler girl with strawberry-blonde curls, rust-coloured
pinafore over a cream long-sleeve top, small brown boots. Full body, standing, carrying a
small sage-green metal watering can with both hands, concentrating with a proud little
smile. Transparent background.

## Job C4 - `kid-baby-10m-sitting.png` (square 1024x1024)
A 10-month-old baby of mixed heritage, oat knit romper, bare feet, sitting up on their
own, full body, big gummy smile, one hand reaching forward. Transparent background.

## Job C5 - `adult-practitioner.png` (portrait 1024x1536)
A warm, professional British South Asian woman in her early 30s, an early-years
practitioner. Hair tied back neatly, minimal make-up, sage-green tunic top with sleeves
pushed up, lanyard WITHOUT any text or card. Waist-up, relaxed folded arms, genuine kind
smile looking at camera. Transparent background.
