#!/bin/bash
# Unix Script to compare english language file with others to show if
# the other language file is out of sync with english
#
# usage: sync.sh <language>
# ex. sync.sh spanish
#
if [ $1 == "" ]
then
        echo "usage: sync.sh <language>"
        exit
fi

cat english.php |grep "lang\\['" |awk -F= {'print $1'} | sort > english.diff
cat $1.php |grep  "lang\\['" |awk -F= {'print $1'} | sort > $1.diff
echo "=============================="
echo "The following phrases are missing from $1.php"
echo "=============================="
diff english.diff $1.diff |grep  "lang\\['" | awk {'print $2'} > missing_phrases.txt
cat missing_phrases.txt |sort
rm english.diff $1.diff missing_phrases.txt
