"""Read-only SSH check on the user's existing LDRN hosting account."""
from pathlib import Path
import json
import paramiko
import socket

private=Path(r'C:\Users\Dhananjay\Local Sites\lrdn\private-releases')
config=json.loads((private/'hosting-connection.json').read_text())
client=paramiko.SSHClient();client.load_system_host_keys()
known=Path.home()/'.ssh/known_hosts'
if known.is_file():client.load_host_keys(str(known))
host_keys=private/'hosting-ssh-known-hosts'
if host_keys.is_file():client.load_host_keys(str(host_keys))
class TrustNewExplicitHost(paramiko.MissingHostKeyPolicy):
    def missing_host_key(self,client,hostname,key):
        if hostname!='['+config['host']+']:65002':raise paramiko.SSHException('Unexpected SSH target.')
        client.get_host_keys().add(hostname,key.get_name(),key);client.save_host_keys(str(host_keys))
client.set_missing_host_key_policy(TrustNewExplicitHost())
try:
    client.connect(config['host'],port=65002,username=config['user'],password=config['password'],timeout=15,auth_timeout=15,banner_timeout=15,allow_agent=True,look_for_keys=True)
    _,out,err=client.exec_command('pwd',timeout=15)
    if out.channel.recv_exit_status()!=0:raise RuntimeError('SSH connected, but shell access is unavailable.')
    print('SSH access verified on the LDRN hosting account.')
    print('Working directory:',out.read().decode().strip())
except paramiko.AuthenticationException:
    print('SSH authentication rejected the available key/agent and the existing main FTP password.')
    raise SystemExit(2)
except (paramiko.SSHException,socket.timeout,OSError) as error:
    print('SSH access unavailable:',type(error).__name__,str(error));raise SystemExit(3)
finally:client.close()
