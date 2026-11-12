"""Back up, transfer and verify the editor save/publish coordination fix over FTPS."""
from pathlib import Path
import importlib.util
import io
import hashlib
import json
import shutil
PROJECT=Path(__file__).resolve().parent.parent
PRIVATE=Path(r'C:\Users\Dhananjay\Local Sites\lrdn\private-releases')
spec=importlib.util.spec_from_file_location('deploy',PROJECT/'scripts/deploy-ldrn.py');deploy=importlib.util.module_from_spec(spec);spec.loader.exec_module(deploy)
source=PROJECT/'wordpress/plugins/ldrn-workspace/assets/studio.js'
shutil.copy2(source,Path(r'C:\Users\Dhananjay\Local Sites\lrdn\app\public\wp-content\plugins\ldrn-workspace\assets\studio.js'))
ftp=deploy.connect(json.loads((PRIVATE/'hosting-connection.json').read_text()))
remote=deploy.LIVE+'/wp-content/plugins/ldrn-workspace/assets/studio.js'
backup=PRIVATE/'20261004T054547Z-fb1c80c0/studio-before-publish-fix.js'
try:
    if not backup.exists():
        with backup.open('xb') as file:ftp.retrbinary('RETR '+remote,file.write)
    with source.open('rb') as file:ftp.storbinary('STOR '+remote,file)
    verified=io.BytesIO();ftp.retrbinary('RETR '+remote,verified.write)
    if hashlib.sha256(verified.getvalue()).hexdigest()!=deploy.digest(source):raise RuntimeError('Uploaded editor file does not match the source.')
    print('Editor save/publish coordination fix deployed over FTPS; original file retained privately and transferred bytes verified.')
finally:ftp.quit()
