#!/bin/bash
set -euo pipefail
SRC_PG=/root/church-web/html/board_skins/picturegallery
DST_PG=/root/church-web/html/modules/board/skins/picturegallery
SRC_SG=/root/church-web/html/board_skins/sermongallery
DST_SG=/root/church-web/html/modules/board/skins/sermongallery

for f in list.html picture_gallery.css picture_gallery.js; do
  cp -f "$SRC_PG/$f" "$DST_PG/$f"
done
for f in list.html church_sermon.css church_sermon.js; do
  if [ -f "$SRC_SG/$f" ]; then
    cp -f "$SRC_SG/$f" "$DST_SG/$f"
  fi
done

# wipe compiled templates so edit buttons appear
rm -f /root/church-web/html/files/cache/template/modules/board/skins/picturegallery/list.html.compiled.php
rm -f /root/church-web/html/files/cache/template/modules/board/skins/sermongallery/list.html.compiled.php
find /root/church-web/html/files/cache/template -path '*picturegallery*list*' -delete 2>/dev/null || true
find /root/church-web/html/files/cache/template -path '*sermongallery*list*' -delete 2>/dev/null || true

touch "$DST_PG/list.html" "$DST_PG/picture_gallery.css" "$DST_SG/list.html" "$DST_SG/church_sermon.css"

echo '=== live skin edit markers ==='
grep -c 'church-pic-edit' "$DST_PG/list.html"
grep -c 'church-sermon-edit' "$DST_SG/list.html"
grep -n '✎\|church-pic-edit' "$DST_PG/list.html" | head -4
