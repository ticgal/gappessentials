#!/bin/bash

CUR_PATH="`dirname \"$0\"`"

cd "$CUR_PATH/.."

xgettext *.php */*.php -o locales/gappessentials.pot -L PHP --add-comments=TRANS --from-code=UTF-8 --force-po -k --keyword=__:1,2t --keyword=_x:1,2,3t --keyword=__s:1,2t --keyword=_sx:1,2,3t --keyword=_n:1,2,3,4t --keyword=_sn:1,2t --keyword=_nx:1,2,3t --copyright-holder "TICgal"

cd locales

sed -i "s/SOME DESCRIPTIVE TITLE/GappEssentials Glpi Plugin/" gappessentials.pot
sed -i "s/FIRST AUTHOR <EMAIL@ADDRESS>, YEAR./TICgal, $(date +%Y)/" gappessentials.pot
sed -i "s/YEAR/$(date +%Y)/" gappessentials.pot

for a in $(ls *.po); do
	msgmerge -U $a gappessentials.pot
	msgfmt $a -o "${a%.*}.mo"
done
rm -f *.po~