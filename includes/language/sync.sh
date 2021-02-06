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

#!/bin/bash
# Unix Script to compare english language file with others to show if
# the other language file is out of sync with english
#
# usage: sync.sh <language-name>
# ex. sync.sh spanish
#

if [ $# -ne 1 ]
then
        echo "usage: sync.sh <language-name>"
	echo "This script will compare the english lang file to the provided language file"
	echo "to determine if there are any missing language phrases"
	echo "Example: 'sync.sh chinese'"
        exit 1
fi

cat english.php |grep "lang\\['" |awk -F= {'print $1'} | sort > english.diff
cat $1.php |grep  "lang\\['" |awk -F= {'print $1'} | sort > $1.diff
echo "=============================="
echo "The following phrases are missing from $1.php"
echo "=============================="
diff english.diff $1.diff |grep  "lang\\['" | awk {'print $2'} > missing_phrases.txt
cat missing_phrases.txt |sort
rm english.diff $1.diff missing_phrases.txt
