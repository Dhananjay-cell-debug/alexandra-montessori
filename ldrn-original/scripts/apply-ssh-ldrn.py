"""Activate the privately verified LDRN release through authenticated SSH."""
from pathlib import Path
import argparse
import hashlib
import importlib.util
import json
import secrets
import shlex
import sys

PROJECT=Path(__file__).resolve().parent.parent
PRIVATE=Path(r'C:\Users\Dhananjay\Local Sites\lrdn\private-releases')
LIVE='/home/u815362143/domains/krildigital.com/public_html/ldrn'

def module(name,file):
    spec=importlib.util.spec_from_file_location(name,PROJECT/'scripts'/file)
    result=importlib.util.module_from_spec(spec);spec.loader.exec_module(result);return result

def main():
    parser=argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--release',required=True)
    parser.add_argument('--config',required=True)
    args=parser.parse_args()
    deploy=module('ldrn_deploy','deploy-ldrn.py')
    release,manifest=deploy.release_path(args.release)
    data=deploy.config(args.config)
    staged=json.loads((release/'ssh-stage.json').read_text())
    remote='/home/u815362143/domains/krildigital.com/ldrn-releases/'+manifest['release']
    if not staged.get('verified') or staged.get('remote')!=remote or staged.get('release')!=manifest['release']:
        raise ValueError('This release has not passed private SSH staging verification.')
    token=secrets.token_urlsafe(48)
    config={key:value for key,value in data.items() if key!='ftp'}
    config['token_sha256']=hashlib.sha256(token.encode()).hexdigest()
    client=module('ldrn_ssh','ssh-ldrn.py').connect()
    try:
        sftp=client.open_sftp()
        try:
            try:sftp.stat(remote+'/status.json')
            except FileNotFoundError:pass
            else:raise ValueError('This release has already been attempted. Inspect its private status; never repeat the import.')
            for name,content in [('remote-config.json',json.dumps(config)),('installer.php',(PROJECT/'scripts/deployment-bootstrap.php').read_text().replace('__LDRN_RELEASE_ID__',manifest['release']))]:
                with sftp.open(remote+'/'+name,'w') as file:file.write(content)
                sftp.chmod(remote+'/'+name,0o600)
        finally:sftp.close()
        stdin,out,err=client.exec_command('php '+shlex.quote(remote+'/installer.php')+' apply '+shlex.quote(LIVE),timeout=300)
        stdin.write(token);stdin.channel.shutdown_write()
        output=out.read().decode();errors=err.read().decode();out.channel.recv_exit_status()
        try:result=json.loads(output)
        except json.JSONDecodeError:raise RuntimeError('Installer did not return its deployment status. Inspect the private server release.')
        if not result.get('ok'):raise RuntimeError('Activation stopped. Inspect private failure-detail.json; the previous website was retained.')
        receipt={'release':manifest['release'],'remote':remote,'backup':'domains/krildigital.com/ldrn-backups/'+manifest['release'],'completed':True}
        (release/'receipt.json').write_text(json.dumps(receipt))
        print('LDRN WordPress and the editor activated through SSH.',flush=True)
        try:deploy.health()
        except Exception:
            deploy.rollback(release,data)
            raise RuntimeError('Live health checks failed. The previous website was restored; the new database is retained for investigation.')
        sftp=client.open_sftp()
        try:
            sftp.remove(remote+'/installer.php')
        finally:sftp.close()
        print('Live routes, content API and WordPress login verified. Credentials remain in the private configuration.')
    finally:client.close()

if __name__=='__main__':
    try:main()
    except Exception as error:
        print('LDRN SSH activation stopped: '+str(error),file=sys.stderr);sys.exit(1)
