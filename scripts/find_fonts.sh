#!/bin/bash
find /usr/share/fonts /var/www -type f \( -name '*.ttf' -o -name '*.otf' \) 2>/dev/null | head -50
fc-list :lang=ko file 2>/dev/null | head -20
