import json
import urllib.request
import urllib.error
for path in ['/wp-json/ldrn/v1/testimonials?per_page=3','/?rest_route=/ldrn/v1/testimonials&per_page=3','/wp-json/']:
    try:
        with urllib.request.urlopen('https://ldrn.krildigital.com'+path,timeout=30) as response:status=response.status;body=response.read().decode()
    except urllib.error.HTTPError as error:status=error.code;body=error.read().decode()
    print(path,status,body[:400])
