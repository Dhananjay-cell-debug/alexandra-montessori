"""Download the latest consistent private live backup and check SQLite integrity."""
from pathlib import Path
import importlib.util, json, re, sqlite3
PROJECT=Path(__file__).resolve().parent.parent
PRIVATE=Path(r'C:\Users\Dhananjay\Local Sites\lrdn\private-releases')
spec=importlib.util.spec_from_file_location('deploy',PROJECT/'scripts/deploy-ldrn.py')
deploy=importlib.util.module_from_spec(spec);spec.loader.exec_module(deploy)
folder='domains/krildigital.com/ldrn-releases/20261004T054547Z-fb1c80c0/database/backups'
ftp=deploy.connect(json.loads((PRIVATE/'hosting-connection.json').read_text()))
try:
    names=sorted(Path(name).name for name in ftp.nlst(folder) if re.fullmatch(r'site-[0-9]{8}-[0-9]{6}-[a-f0-9]{8}\.sqlite',Path(name).name))
    if not names:raise RuntimeError('No consistent private backup found.')
    target=PRIVATE/'live-backups'/names[-1];target.parent.mkdir(exist_ok=True)
    with target.open('wb') as out:ftp.retrbinary('RETR '+folder+'/'+names[-1],out.write)
finally:ftp.quit()
with sqlite3.connect(target.as_uri()+'?mode=ro',uri=True) as db:
    integrity=db.execute('PRAGMA integrity_check').fetchone()[0]
    if integrity!='ok':raise RuntimeError('Backup integrity check failed.')
    tables=db.execute("SELECT count(*) FROM sqlite_master WHERE type='table'").fetchone()[0]
result={'integrity':integrity,'tables':tables,'bytes':target.stat().st_size,'local_private_backup':str(target),'remote_private_folder':folder}
(PRIVATE/'live-backup-verification.json').write_text(json.dumps(result,indent=2))
print(json.dumps(result,indent=2))
