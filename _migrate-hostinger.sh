#!/usr/bin/env bash
# Migrate Alexandra React build -> Hostinger subdomain alexandra.krildigital.com
# Phases: backup | wipe | upload | verify  (pass as $1)
set -uo pipefail

H='31.170.167.196'
U='u815362143'
PASS="${FTP_PASS:?set FTP_PASS}"
B="domains/krildigital.com/public_html/alexandra"          # remote doc root (relative to FTP home)
BK="${BK:-/c/DHANANJAY/claude code/CLIENT PROJECTS/alexandra montesorri/_hostinger-backup-alexandra-$(date +%Y%m%d-%H%M%S)}"
DIST="/c/DHANANJAY/claude code/CLIENT PROJECTS/alexandra montesorri/alexandra-montessori/dist"

CURL=(curl -sS --connect-timeout 30 --ssl -k --user "$U:$PASS")

# Recursively walk the remote alexandra folder. For each entry call back with: <type d|f> <relpath>
walk() {
  local rel="$1"
  local url="ftp://$H/$B${rel:+/$rel}/"
  local listing; listing=$("${CURL[@]}" "$url")
  local line
  while IFS= read -r line; do
    [ -z "$line" ] && continue
    local d="${line:0:1}"
    local name; name=$(printf '%s\n' "$line" | awk '{print $NF}')
    [ "$name" = "." ] && continue
    [ "$name" = ".." ] && continue
    local child="${rel:+$rel/}$name"
    if [ "$d" = "d" ]; then
      printf 'DIR  %s\n' "$child"
      walk "$child"
    else
      printf 'FILE %s\n' "$child"
    fi
  done <<< "$listing"
}

backup() {
  echo ">> BACKUP remote alexandra/ -> $BK"
  mkdir -p "$BK"
  walk "" | while read -r typ rel; do
    if [ "$typ" = "DIR" ]; then
      mkdir -p "$BK/$rel"
    else
      mkdir -p "$BK/$(dirname "$rel")"
      "${CURL[@]}" "ftp://$H/$B/$rel" -o "$BK/$rel" && echo "  saved $rel"
    fi
  done
  echo ">> backup file count: $(find "$BK" -type f | wc -l)"
}

# delete contents of alexandra/ but keep the folder itself
wipe_dir() {
  local rel="$1"
  local url="ftp://$H/$B${rel:+/$rel}/"
  local listing; listing=$("${CURL[@]}" "$url")
  local line
  while IFS= read -r line; do
    [ -z "$line" ] && continue
    local d="${line:0:1}"
    local name; name=$(printf '%s\n' "$line" | awk '{print $NF}')
    [ "$name" = "." ] && continue
    [ "$name" = ".." ] && continue
    if [ "$d" = "d" ]; then
      wipe_dir "${rel:+$rel/}$name"
      "${CURL[@]}" "$url" -Q "+RMD $name" -o /dev/null && echo "  rmdir ${rel:+$rel/}$name"
    else
      "${CURL[@]}" "$url" -Q "+DELE $name" -o /dev/null && echo "  del   ${rel:+$rel/}$name"
    fi
  done <<< "$listing"
}

wipe() {
  echo ">> WIPE remote alexandra/ contents"
  wipe_dir ""
  echo ">> after wipe, remaining:"
  "${CURL[@]}" "ftp://$H/$B/"
}

upload() {
  echo ">> UPLOAD dist/ -> alexandra/"
  cd "$DIST" || exit 1
  find . -type f | while read -r f; do
    rel="${f#./}"
    "${CURL[@]}" --ftp-create-dirs -T "$f" "ftp://$H/$B/$rel" && echo "  up  $rel"
  done
  echo ">> upload done"
}

verify() {
  echo ">> remote alexandra/ after upload:"
  "${CURL[@]}" "ftp://$H/$B/"
}

"$1"
