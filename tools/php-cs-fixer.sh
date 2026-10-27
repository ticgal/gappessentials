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

TOOL_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" >/dev/null 2>&1 && pwd)"
cd "$TOOL_DIR/.."

if [ ! -f ".php-cs-fixer.php" ]; then
    echo "Error: The configuration file doesn't exist in .php-cs-fixer.php"
    exit 1
fi

if [ ! -f "vendor/bin/php-cs-fixer" ]; then
    echo "Error: PHP CS Fixer not found in vendor/bin/php-cs-fixer"
    exit 1
fi

# vendor/bin/php-cs-fixer check --config=.php-cs-fixer.php to check problems
PHP_CS_FIXER_IGNORE_ENV=1 vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.php --no-interaction