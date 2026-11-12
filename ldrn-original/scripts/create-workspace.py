"""One-time port of the reference canvas; never writes to the INU installation."""
from pathlib import Path
import re

root = Path(__file__).resolve().parents[1]
reference = Path(r'C:\Users\Dhananjay\Local Sites\inu-media\app\public\wp-content\plugins\inu-media-workspace')
target = root / 'wordpress/plugins/ldrn-workspace'
for folder in ('assets', 'includes'):
    (target / folder).mkdir(parents=True, exist_ok=True)

def port(text):
    for old, new in [('ww_ws_', 'ldrn_ws_'), ('ww_studio_', 'ldrn_ws_studio_'),
                     ('ww_studio', 'ldrn_ws_studio'), ('WW_WS_', 'LDRN_WS_'),
                     ('WW_STUDIO_', 'LDRN_WS_STUDIO_'), ('__WW__', '__LDRN_WORKSPACE__'),
                     ('__wwStudio', '__ldrnStudio'), ('wwStudio', 'ldrnStudio'),
                     ('data-ww', 'data-ldrn-ws'), ('ww-studio', 'ldrn-studio'),
                     ('ww-sessions', 'ldrn-sessions'), ('ww_manage_site', 'manage_options'),
                     ('INU Media', 'Little Rainbows'), ('inu-media', 'ldrn-workspace'),
                     ('INUDisplay, Archivo, sans-serif', 'Fraunces, Georgia, serif'),
                     ('INUBrush, cursive', 'Fraunces, Georgia, serif'),
                     ('Archivo, Arial, sans-serif', '"Nunito Sans", sans-serif'),
                     ('INU Display', 'Fraunces'), ('INU Brush', 'Fraunces italic'), ('Archivo', 'Nunito Sans')]:
        text = text.replace(old, new)
    return text

for file in ('assets/studio.js', 'assets/studio.css', 'assets/studio-runtime.js', 'includes/studio.php', 'includes/pages.php'):
    content = port((reference / file).read_text(encoding='utf-8-sig'))
    if file.endswith('studio-runtime.js'):
        content = content.replace("'.site-shell'", "'#root'")
        content = content.replace("el.classList && el.classList.contains('site-shell')", "el.id === 'root'")
        content = content.replace("var ALIASES = { '/contact': '/lets-talk', '/index': '/', '/index.html': '/' };", "var ALIASES = { '/index': '/', '/index.html': '/', '/fees-and-funding': '/funded-childcare' };")
        content = content.replace("function schedule() {", "function schedule() {\n    var nextRoute = readRoute();\n    if (nextRoute !== route) { route = nextRoute; D.documentElement.setAttribute('data-ldrn-ws-route', route); paintStyles(); if (EDIT) host('route', {route: route}); }")
        content = content.replace("'.ww-not-found", "'.ldrn-not-found")
        content = content.replace(".ww-not-found{", ".ldrn-not-found{")
    if file.endswith('pages.php'):
        labels = [('Home','/'),('Our Nursery','/settings'),('Hounslow','/settings/hounslow'),('About','/about'),('Ofsted','/about/ofsted'),('Our Curriculum','/curriculum'),('Events','/events'),('Fees','/fees'),('Fee Calculator','/fee-calculator'),('Funded Childcare','/funded-childcare'),('Blog','/blogs'),('Food Hygiene Rating','/food-hygiene-rating'),('Admissions','/admissions'),('Book a Visit','/book-a-visit'),('Check Availability','/check-availability'),('Careers','/careers'),('Vacancies','/careers/vacancies'),('Apply','/careers/apply'),('Parent Stories','/testimonials'),('Contact Us','/contact'),('Privacy','/privacy')]
        rows = []
        for label, route in labels:
            ident = route.strip('/').replace('/', '-') or 'home'
            group = 'primary' if route in ('/','/settings','/curriculum','/events','/careers','/contact') else 'other'
            rows.append(f"array('id'=>'{ident}', 'label'=>'{label}', 'route'=>'{route}', 'group'=>'{group}', 'visible'=>true, 'lede'=>'{label}'),")
        content = re.sub(r'function ldrn_ws_default_pages\(\) \{.*?\n\}', "function ldrn_ws_default_pages() {\n    return array(\n        " + '\n        '.join(rows) + "\n    );\n}", content, count=1, flags=re.S)
    if file.endswith('studio.php'):
        replacement = "function ldrn_ws_studio_builtin_route($route) {\n    return in_array($route, wp_list_pluck(ldrn_ws_default_pages(), 'route'), true)\n        || preg_match('#^/(settings|events|blogs|careers/vacancies)/[^/]+$#', $route)\n        || preg_match('#^/(wp-|assets|fonts|wp-content|wp-json)#', $route);\n}"
        content = re.sub(r'function ldrn_ws_studio_builtin_route\(\$route\) \{.*?\n\}', replacement, content, count=1, flags=re.S)
        content = re.sub(r'function ldrn_ws_studio_site_images\(\) \{.*?\n\}', "function ldrn_ws_studio_site_images() { return ldrn_ws_images(); }", content, count=1, flags=re.S)
        # Self-hosted editor fonts: remove the external font enqueue.
        content = re.sub(r"    wp_enqueue_style\(\s*'ldrn-studio-fonts',.*?\n    \);", '', content, count=1, flags=re.S)
        content = content.replace("array('ldrn-studio-fonts')", 'array()')
        content = content.replace("'pages' => ldrn_ws_studio_custom_pages(true)", "'pages' => $canvas ? ldrn_ws_studio_custom_pages(true) : ldrn_ws_published_custom_pages()")
    (target / file).write_text(content, encoding='utf-8')

# Keep the source identities in the source, so later additions do not renumber
# existing sections. Dynamic records receive their own namespace.
for page in (root / 'frontend/src/pages').glob('*.jsx'):
    content = page.read_text(encoding='utf-8')
    if 'data-ldrn-ws=' in content:
        continue
    count = [0]
    stem = re.sub(r'([a-z])([A-Z])', r'\1-\2', page.stem).lower()
    def anchor(match):
        count[0] += 1
        name = f'{stem}.section-{count[0]}'
        dynamic = page.stem in ('SettingDetail', 'EventDetail')
        value = "{'" + stem + ".' + slug + '.section-" + str(count[0]) + "'}" if dynamic else '"' + name + '"'
        return '<section data-ldrn-ws=' + value
    content = re.sub(r'<section\b', anchor, content)
    page.write_text(content, encoding='utf-8')
print('Ported canvas into the independent LDRN workspace; source anchors added.')
