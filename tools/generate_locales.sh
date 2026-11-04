#!/bin/bash

#
#  -------------------------------------------------------------------------
#  GappEssentials plugin for GLPI
#  Copyright (C) 2019 - 2026 by TICGAL.
#  https://tic.gal
#  https://github.com/pluginsGLPI/gappessentials
#  -------------------------------------------------------------------------
#
#  LICENSE
#
#  This file is part of GappEssentials.
#
#  GappEssentials is free software; you can redistribute it and/or modify
#  it under the terms of the GNU General Public License as published by
#  the Free Software Foundation; either version 2 of the License, or
#  (at your option) any later version.
#
#  GappEssentials is distributed in the hope that it will be useful,
#  but WITHOUT ANY WARRANTY; without even the implied warranty of
#  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#  GNU General Public License for more details.
#
#  You should have received a copy of the GNU General Public License
#  along with GappEssentials. If not, see <http://www.gnu.org/licenses/>.
#  --------------------------------------------------------------------------
#  * @package   gappessentials
#  * @author    the TICGAL team
#  * @copyright Copyright (C) 2019 - 2026 TICGAL team
#  * @license   AGPL License 3.0 or (at your option) any later version
#  *            http://www.gnu.org/licenses/agpl-3.0-standalone.html
#  * @link      https://www.tic.gal
#  * @since     2019
#  * -------------------------------------------------------------------------
#

CUR_PATH="`dirname \"$0\"`"

cd "$CUR_PATH/.."

xgettext *.php */*.php */*/*.php -o locales/gappessentials.pot -L PHP --add-comments=TRANS --from-code=UTF-8 --force-po -k --keyword=__:1,2t --keyword=_x:1,2,3t --keyword=__s:1,2t --keyword=_sx:1,2,3t --keyword=_n:1,2,3,4t --keyword=_sn:1,2t --keyword=_nx:1,2,3t --copyright-holder "TICGAL"

cd locales

sed -i "s/SOME DESCRIPTIVE TITLE/GappEssentials Glpi Plugin/" gappessentials.pot
sed -i "s/FIRST AUTHOR <EMAIL@ADDRESS>, YEAR./TICGAL, $(date +%Y)/" gappessentials.pot
sed -i "s/YEAR/$(date +%Y)/" gappessentials.pot

for a in $(ls *.po); do
	msgmerge -U $a gappessentials.pot
	msgfmt $a -o "${a%.*}.mo"
done
rm -f *.po~