set -eu
printf 'Available deployment tools\n'
for tool in php wp mysql mariadb hapi hpanel hostinger-cli uapi; do command -v "$tool" || true; done
printf 'PHP runtime\n'
php -r 'echo PHP_VERSION,"\n"; foreach(["pdo_mysql","mysqli","zlib","mbstring","openssl"] as $name)echo $name,": ",extension_loaded($name)?"yes":"no","\n";'
printf 'Account-owned MySQL configuration\n'
if [ -r /home/u815362143/.my.cnf ]; then printf 'MySQL defaults file exists\n'; else printf 'No MySQL defaults file\n'; fi
printf 'Default MySQL access\n'
if command -v mysql >/dev/null 2>&1; then mysql --connect-timeout=5 -N -e 'SELECT CURRENT_USER()' 2>&1 || true; fi
printf 'LDRN target\n'
ls -ld /home/u815362143/domains/krildigital.com/public_html/ldrn
printf 'Hosting account control helpers\n'
find /usr/local/bin -maxdepth 1 -type f \( -iname '*hostinger*' -o -iname '*hpanel*' -o -iname '*hapi*' -o -iname '*mysql*' \) -print 2>/dev/null || true
