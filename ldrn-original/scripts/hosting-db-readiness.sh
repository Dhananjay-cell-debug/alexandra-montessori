set -eu
printf 'Account-level configuration locations\n'
find /home/u815362143 -maxdepth 1 -type f -printf '%f\n' | sort
printf 'Hosting configuration directories\n'
for folder in /home/u815362143/.config /home/u815362143/.hostinger /home/u815362143/.hpanel; do
  if [ -d "$folder" ]; then find "$folder" -maxdepth 1 -type f -printf '%f\n'; fi
done
printf 'LDRN domain configuration files\n'
find /home/u815362143/domains/krildigital.com -maxdepth 1 -type f \( -iname '*config*' -o -iname '*.env*' -o -iname '*database*' \) -printf '%f\n'
