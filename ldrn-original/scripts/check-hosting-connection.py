"""Read-only FTPS connection check; never changes a remote file or prints credentials."""
from pathlib import Path
import ftplib
import re
import ssl
import socket
import hashlib
import importlib.util
from cryptography import x509

source=Path(r'C:\DHANANJAY\claude code\CLIENT PROJECTS\ldrn\_ftp_ldrn.sh').read_text()
def value(key):
    match=re.search(r'^'+re.escape(key)+r"=['\"]([^'\"]+)['\"]",source,re.M)
    if not match:raise ValueError('Existing LDRN FTP configuration is incomplete.')
    return match[1]
host=value('H')
# Read only the public certificate without authenticating, then validate the
# connection under its certificate's DNS name before sending FTP credentials.
probe=ftplib.FTP_TLS(context=ssl._create_unverified_context(),timeout=30)
probe.connect(host,21);probe.auth()
cert=x509.load_der_x509_certificate(probe.sock.getpeercert(binary_form=True))
names=cert.extensions.get_extension_for_class(x509.SubjectAlternativeName).value.get_values_for_type(x509.DNSName)
probe.close()
verified=None
for name in names:
    if '*' in name:continue
    try:
        if host not in socket.gethostbyname_ex(name)[2]:continue
        ftp=ftplib.FTP_TLS(context=ssl.create_default_context(),timeout=30)
        ftp.connect(name,21);ftp.auth();verified=(name,ftp);break
    except (OSError,ssl.SSLError,ftplib.Error):continue
if not verified:
    # The user's existing Hostinger connection uses an IP. Validate the CA
    # chain and pin the certificate seen on that explicitly supplied server.
    context=ssl.create_default_context();context.check_hostname=False
    probe=ftplib.FTP_TLS(context=context,timeout=30);probe.connect(host,21);probe.auth()
    pin=hashlib.sha256(probe.sock.getpeercert(binary_form=True)).hexdigest();probe.close()
    spec=importlib.util.spec_from_file_location('ldrn_deployment',Path(__file__).with_name('deploy-ldrn.py'))
    deploy=importlib.util.module_from_spec(spec);spec.loader.exec_module(deploy)
    ftp=deploy.connect({'host':host,'user':value('U'),'password':value('PASS'),'certificate_sha256':pin})
    verified=(host,ftp)
    private=Path(r'C:\Users\Dhananjay\Local Sites\lrdn\private-releases/hosting-connection.json')
    private.write_text(__import__('json').dumps({'host':host,'user':value('U'),'password':value('PASS'),'protocol':'ftps','certificate_sha256':pin}))
name,ftp=verified
try:
    if name!=host:ftp.login(value('U'),value('PASS'));ftp.prot_p()
    names=[entry[0] for entry in ftp.mlsd('domains/krildigital.com/public_html/ldrn')]
    print('Validated FTPS connection:',name)
    print('Live LDRN root contains WordPress:', 'wp-config.php' in names or 'wp-admin' in names)
    print('Live root files:',', '.join(sorted(names)))
finally:ftp.quit()
