"""Stage a verified LDRN release privately over the verified SSH connection."""
from pathlib import Path
import argparse
import importlib.util
import json
import re
import shlex
import tarfile

PROJECT=Path(__file__).resolve().parent.parent
PRIVATE=Path(r'C:\Users\Dhananjay\Local Sites\lrdn\private-releases')
parser=argparse.ArgumentParser();parser.add_argument('--release',required=True);args=parser.parse_args()
release=Path(args.release).resolve()
if not release.is_relative_to(PRIVATE):raise ValueError('Expected a prepared private LDRN release.')
spec=importlib.util.spec_from_file_location('ldrn_deploy',PROJECT/'scripts/deploy-ldrn.py')
deploy=importlib.util.module_from_spec(spec);spec.loader.exec_module(deploy)
release,manifest=deploy.release_path(str(release))
if not re.fullmatch(r'[0-9]{8}T[0-9]{6}Z-[a-f0-9]{8}',manifest['release']):raise ValueError('Invalid release identity.')
archive=release/'release.tar.gz'
with tarfile.open(archive,'w:gz',compresslevel=6) as target:
    target.add(release/'public',arcname='public')
    for name in ['manifest.json','database.sql.gz']:target.add(release/name,arcname=name)
print('Prepared private SSH archive: %.1f MiB'%(archive.stat().st_size/1048576),flush=True)
spec=importlib.util.spec_from_file_location('ldrn_ssh',PROJECT/'scripts/ssh-ldrn.py')
ssh=importlib.util.module_from_spec(spec);spec.loader.exec_module(ssh)
client=ssh.connect()
remote='/home/u815362143/domains/krildigital.com/ldrn-releases/'+manifest['release']
def run(command,timeout=120):
    _,out,err=client.exec_command(command,timeout=timeout)
    output=out.read().decode();error=err.read().decode();status=out.channel.recv_exit_status()
    if output:print(output,end='',flush=True)
    if status:raise RuntimeError('Remote staging failed: '+error)
try:
    run('umask 077; mkdir -p '+shlex.quote(remote))
    sftp=client.open_sftp();progress=[0]
    def report(done,total):
        percentage=int(done*100/total)
        if percentage>=progress[0]+20:progress[0]=percentage;print('Private upload: '+str(percentage)+'%',flush=True)
    sftp.put(str(archive),remote+'/release.tar.gz',callback=report);sftp.close()
    run('tar -xzf '+shlex.quote(remote+'/release.tar.gz')+' -C '+shlex.quote(remote))
    code='''<?php
    $root=__ROOT__;
    $manifest=json_decode(file_get_contents($root.'/manifest.json'),true);
    foreach($manifest['files'] as $rel=>$hash)if(!is_file($root.'/public/'.$rel) || !hash_equals($hash,hash_file('sha256',$root.'/public/'.$rel)))throw new RuntimeException('Release file mismatch.');
    if(!hash_equals($manifest['sql_sha256'],hash_file('sha256',$root.'/database.sql.gz')))throw new RuntimeException('SQL mismatch.');
    echo count($manifest['files'])." files verified outside public_html. Live site unchanged.\\n";
    '''.replace('__ROOT__',json.dumps(remote))
    stdin,out,err=client.exec_command('php',timeout=120);stdin.write(code);stdin.channel.shutdown_write()
    output=out.read().decode();error=err.read().decode();status=out.channel.recv_exit_status()
    if status:raise RuntimeError('Remote release verification failed: '+error)
    print(output,end='');(release/'ssh-stage.json').write_text(json.dumps({'release':manifest['release'],'remote':remote,'verified':True}))
finally:client.close()
