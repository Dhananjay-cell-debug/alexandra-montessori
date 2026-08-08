import os, re
files = ['src/components/Seo.jsx', 'src/pages/About.jsx', 'src/pages/Fees.jsx', 'src/pages/Home.jsx', 'src/pages/Curriculum.jsx', 'src/pages/NurseryDetail.jsx', 'src/data/site.js', 'src/pages/Careers.jsx']
total = 0
for f in files:
    if os.path.exists(f):
        content = open(f, 'r', encoding='utf-8').read()
        matches = re.findall(r'([\'\"`])(/assets/(?:photos|client|art)/[^\'\"`]+|\$\{[CPA]\}/[^\'\"`]+)\1', content)
        print(f'{f}: {len(matches)} matches')
        total += len(matches)
print('Total:', total)
