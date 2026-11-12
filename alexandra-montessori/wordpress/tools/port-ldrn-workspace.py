"""Copy LDRN's Site Editor plugin (ldrn-workspace) into Alexandra as alexandra-workspace.

The code is taken as-is; only identifiers, brand and Alexandra's own WordPress
names are swapped, in this fixed order. Site-specific content (page directory,
templates, palette, snapshot data) is then edited by hand in the copy.

usage: python port-ldrn-workspace.py <ldrn-workspace dir> <target dir>
"""
import os, shutil, sys

SRC, DST = sys.argv[1], sys.argv[2]
SKIP = {'studio-runtime.live.js'}  # generated at runtime from studio-runtime.js

MAP = [
    ('LDRN_CONTENT_MANAGER_ROLE', 'AM_CONTENT_MANAGER_ROLE'),
    ('__LDRN_WORKSPACE__', '__AM_WORKSPACE__'),
    ('__LDRN_SESSION__', '__AM_SESSION__'),
    ('LDRN_WS_', 'AM_WS_'),
    ('ldrn_ws_', 'am_ws_'),
    ('ldrn-ws', 'am-ws'),
    ('ldrn_session', 'am_session'),
    ('ldrn_workspace', 'am_workspace'),
    ('ldrn-studio', 'am-studio'),
    ('ldrnStudio', 'amStudio'),
    ('ldrnWorkspace', 'amWorkspace'),
    ('ldrn-workspace', 'am-workspace'),
    ('ldrnSessions', 'amSessions'),
    ('ldrn-sessions', 'am-sessions'),
    ('ldrn-activity', 'am-activity'),
    ('ldrnSnapshot', 'amSnapshot'),
    ('ldrn-snapshot', 'am-snapshot'),
    ('ldrn-site-builds', 'am-site-builds'),
    ('ldrn-collection', 'am-collection'),
    ('ldrn-link-for', 'am-link-for'),
    ('ldrn-not-found', 'am-not-found'),
    ('ldrnData', 'amData'),
    ('ldrn-theme', 'alexandra-theme'),
    ('ldrn-app', 'alexandra-app'),
    ("'ldrn_content'", "'am_content'"),
    ('ldrn_dist_path', 'alexandra_dist_path'),
    ('ldrn_dist_uri', 'alexandra_dist_uri'),
    ('ldrn_cms_data', 'am_cms_data'),
    ('ldrn_seo_', 'am_seo_'),
    ('ldrn_setting', 'am_nursery'),
    ('ldrn_event', 'am_event'),
    ('ldrn_testimonial', 'am_testimonial'),
    ('ldrn_job', 'am_job'),
    ('ldrn_blog', 'am_blog'),
    ('ldrn_get_', 'am_get_'),
    ('ldrn_ready_blog_posts', 'am_ready_blog_posts'),
    ('ldrn_prepare_', 'am_prepare_'),
    ('ldrn_about', 'am_about'),
    ('ldrn_site_settings', 'am_settings'),
    ('_ldrn_', '_am_'),
    ('/ldrn/v1/', '/alexandra/v1/'),
    ('Little Rainbows Day Nursery', 'Alexandra Montessori'),
    ('Little Rainbows Workspace', 'Alexandra Montessori Workspace'),
    ('Little Rainbows', 'Alexandra Montessori'),
    ('LDRN', 'Alexandra'),
]

def port(text):
    for a, b in MAP:
        text = text.replace(a, b)
    return text

for root, dirs, files in os.walk(SRC):
    rel = os.path.relpath(root, SRC)
    os.makedirs(os.path.join(DST, rel), exist_ok=True)
    for name in files:
        if name in SKIP:
            continue
        out = port(name).replace('ldrn-workspace', 'alexandra-workspace')
        if name == 'ldrn-workspace.php':
            out = 'alexandra-workspace.php'
        with open(os.path.join(root, name), encoding='utf-8', newline='') as f:
            text = f.read()
        with open(os.path.join(DST, rel, out), 'w', encoding='utf-8', newline='') as f:
            f.write(port(text))
        print(os.path.join(rel, out))
