import os, re
import random

files = ['src/components/Seo.jsx', 'src/pages/About.jsx', 'src/pages/Fees.jsx', 'src/pages/Home.jsx', 'src/pages/Curriculum.jsx', 'src/pages/NurseryDetail.jsx', 'src/data/site.js', 'src/pages/Careers.jsx']

available_images = [
    "/assets/photos/activity-1.webp",
    "/assets/photos/classroom-wide-1.webp",
    "/assets/photos/classroom-wide-2.webp",
    "/assets/photos/classroom-wide-3.webp",
    "/assets/photos/materials.webp",
    "/assets/photos/nature-discovery.webp",
    "/assets/photos/portrait-1.webp",
    "/assets/photos/portrait-2.webp",
    "/assets/photos/portrait-3.webp",
    "/assets/photos/portrait-4.webp",
    "/assets/photos/portrait-5.webp",
    "/assets/photos/portrait-6.webp",
    "/assets/photos/toddler-crayon.webp",
    "/assets/photos/toddler-helper.webp",
    "/assets/photos/toddler-reading.webp",
    "/assets/photos/toddler-watering.webp",
    "/assets/client/button-up-activity.png",
    "/assets/client/colour-mixing-activity.png",
    "/assets/client/cooking-activity.png",
    "/assets/client/english-activity.png",
    "/assets/client/float-or-sink.png",
    "/assets/client/focus-activity.png",
    "/assets/client/gardening-activity.png",
    "/assets/client/lacing-activity.png",
    "/assets/client/maths-activity.png",
    "/assets/client/microscope-activity.png",
    "/assets/client/montessori-activities-grid.png",
    "/assets/client/picking-sensory-activity.png",
    "/assets/client/recycle-activity.png",
    "/assets/client/sensory-play.png",
    "/assets/client/volcano-science-activity.png",
    "/assets/art/artist.webp",
    "/assets/art/bg-castle.webp",
    "/assets/art/cooking.webp",
    "/assets/art/gardening.webp",
    "/assets/art/listener.webp",
    "/assets/art/painting.webp",
    "/assets/art/philosopher.webp",
    "/assets/art/reading.webp",
    "/assets/art/scientist.webp",
    "/assets/videos/children-wooden-toys-poster.webp",
    "/assets/photos/owners.webp"
]

random.seed(42)
random.shuffle(available_images)

index = 0

def replacer(match):
    global index
    img = available_images[index % len(available_images)]
    index += 1
    # Check if the original match was a template literal with ${C}, ${P} or ${A}
    orig_path = match.group(2)
    quote = match.group(1)
    # the replacement must use the same quote. But if orig_path had ${C}, we shouldn't break other things.
    # Actually, we can just replace the whole path with the absolute path string. 
    # But wait, site.js has `...` and we'd be replacing `${C}/...` with `/assets/...`. This is perfectly valid!
    # Because `${C}` is just a template string variable. 
    # BUT we need to be careful if it was enclosed in backticks, it will become `/assets/...`. This is a valid string in JS.
    # So we just return quote + img + quote
    return quote + img + quote

for f in files:
    if os.path.exists(f):
        content = open(f, 'r', encoding='utf-8').read()
        new_content = re.sub(r'([\'\"`])(/assets/(?:photos|client|art|videos)/[^\'\"`]+|\$\{[CPA]\}/[^\'\"`]+)\1', replacer, content)
        open(f, 'w', encoding='utf-8').write(new_content)

print(f"Replaced {index} occurrences using {len(available_images)} available images.")
