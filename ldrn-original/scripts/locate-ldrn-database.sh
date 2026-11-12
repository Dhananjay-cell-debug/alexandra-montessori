set -eu
printf 'Current LDRN website files\n'
find /home/u815362143/domains/krildigital.com/public_html/ldrn -maxdepth 2 -type f \( -name 'wp-config.php' -o -name '.env' -o -iname '*database*' -o -iname '*config*.json' -o -iname '*config*.php' \) -printf '%p\n'
printf 'LDRN private configuration file names\n'
find /home/u815362143/domains/krildigital.com -maxdepth 3 -type f \( -name '.env*' -o -name '*ldrn*config*' -o -name '*lrdn*config*' -o -name '*database*.json' -o -name 'remote-config.json' \) -printf '%p\n'
printf 'LDRN static root metadata\n'
ls -ld /home/u815362143/domains/krildigital.com/public_html/ldrn
printf 'Direct PHP/MySQL authenticated connection\n'
mysql --connect-timeout=5 -N -e 'SELECT CURRENT_USER()' 2>&1 || true
