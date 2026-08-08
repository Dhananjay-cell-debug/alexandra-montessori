import re

# Fix NurseryDetail.jsx
path = 'src/pages/NurseryDetail.jsx'
c = open(path, 'r', encoding='utf-8').read()
# Removing Tea and Snacks from mealMoments
c = re.sub(
    r'(const mealMoments = \[[\s\S]+?)\{\s*label:\s*\"Tea\"[\s\S]+?\{\s*label:\s*\"Snacks\"[\s\S]+?\}\s*,?\s*\n\];',
    r'\1];',
    c
)
open(path, 'w', encoding='utf-8').write(c)

# Fix site.js staticLocations gallery length (remove the last item of each 3-item gallery)
path = 'src/data/site.js'
c = open(path, 'r', encoding='utf-8').read()
c = re.sub(
    r'(gallery:\s*\[\s*`\$\{C\}/[^`]+`,\s*`\$\{C\}/[^`]+`),\s*`\$\{C\}/[^`]+`\s*\]',
    r'\1\n    ]',
    c
)
open(path, 'w', encoding='utf-8').write(c)
