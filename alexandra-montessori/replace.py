
import os, re
import random

site_js_path = 'src/data/site.js'
content = open(site_js_path, 'r', encoding='utf-8').read()

# Replace galleryImages array with reference to careersGallery
content = re.sub(
    r'export const galleryImages = \[[^\]]+\];',
    'export const galleryImages = careersGallery;',
    content
)

open(site_js_path, 'w', encoding='utf-8').write(content)

