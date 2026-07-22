#!/bin/bash
# Copyright (C) 2000-2021. Stephen Lawrence
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
#
# Compare english language file with others to show if
# the other language file is out of sync with english
#
# usage: sync.sh <language-name>
#        sync.sh --all
#
# ex.    sync.sh spanish
#        sync.sh --all

extract_keys() {
    # Normalize $lang ['key'] -> $lang['key'] then extract key names
    sed 's/\$lang *\[/\$lang[/g' "$1" | grep "lang\['" | awk -F= '{print $1}' | sort -u
}

if [ "$1" = "--all" ]; then
    languages=()
    for f in *.php; do
        [ "$f" = "english.php" ] && continue
        languages+=("${f%.php}")
    done

    english_keys=$(extract_keys english.php)
    any_missing=0
    for lang in "${languages[@]}"; do
        other_keys=$(extract_keys "$lang.php")
        missing=$(comm -23 <(echo "$english_keys") <(echo "$other_keys"))
        if [ -n "$missing" ]; then
            echo "=============================="
            echo "Missing from $lang.php"
            echo "=============================="
            echo "$missing"
            echo ""
            any_missing=1
        fi
    done
    [ "$any_missing" -eq 0 ] && echo "All language files are in sync with english.php"
    exit $any_missing
fi

if [ $# -ne 1 ]; then
    echo "usage: sync.sh <language-name>"
    echo "       sync.sh --all"
    echo ""
    echo "Compares the english lang file to the provided language file"
    echo "to determine if there are any missing language phrases"
    echo "Example: 'sync.sh chinese'"
    exit 1
fi

english_keys=$(extract_keys english.php)
other_keys=$(extract_keys "$1.php")
missing=$(comm -23 <(echo "$english_keys") <(echo "$other_keys"))
echo "=============================="
echo "The following phrases are missing from $1.php"
echo "=============================="
echo "$missing"
[ -n "$missing" ] && exit 1 || exit 0
