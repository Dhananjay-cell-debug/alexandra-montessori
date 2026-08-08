import re
content = open('src/data/site.js', 'r', encoding='utf-8').read()
matches = re.findall(r'([\'\"`])(/assets/(?:photos|client|art)/[^\'\"`]+|\$\{[CPA]\}/[^\'\"`]+)\1', content)
for m in matches:
    print(m[1])
