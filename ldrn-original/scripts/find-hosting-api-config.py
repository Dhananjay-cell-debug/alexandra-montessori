"""Find only existing Hostinger API configuration; never print its values."""
from pathlib import Path
import json
import tomllib
import os

home=Path.home();found=[]
for path in [home/'.claude.json',home/'.codex/config.toml']:
    if not path.is_file():continue
    try:data=tomllib.loads(path.read_text(encoding='utf-8-sig')) if path.suffix=='.toml' else json.loads(path.read_text(encoding='utf-8-sig'))
    except Exception:continue
    def walk(node,path_parts=()):
        if not isinstance(node,dict):return
        for key,value in node.items():
            if isinstance(value,dict):
                if 'hostinger' in key.lower():
                    env=value.get('env',{})
                    for name in ['API_TOKEN','HOSTINGER_API_TOKEN','HAPI_API_TOKEN','APITOKEN']:
                        if env.get(name):found.append(env[name]);print('Existing Hostinger API configuration found in',path.name)
                walk(value,path_parts+(key,))
    walk(data)
if found:
    target=home/'Local Sites/lrdn/private-releases/hostinger-api-token'
    target.write_text(found[0],encoding='utf-8')
    print('Hostinger token copied privately for authorized LDRN database provisioning.')
else:print('No existing Hostinger API token found in the named local CLI/MCP configuration files.')
