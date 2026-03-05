<?php
/*
 -------------------------------------------------------------------------
 GappEssentials plugin for GLPI
 Copyright (C) 2019 - 2026 by the TICGAL
 https://tic.gal
 https://github.com/pluginsGLPI/gappessentials
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GappEssentials.

 GappEssentials is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GappEssentials is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GappEssentials. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 * @package   gappessentials
 * @author    the TICGAL team
 * @copyright Copyright (C) 2019 - 2026 TICGAL team
 * @license   AGPL License 3.0 or (at your option) any later version
 *            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 * @link      https://www.tic.gal
 * @since     2019
 * -------------------------------------------------------------------------
 */

use Glpi\Plugin\Hooks;

define('PLUGIN_GAPPESSENTIALS_VERSION', '3.0.0-beta4');
// Minimal GLPI version, inclusive
define("PLUGIN_GAPPESSENTIALS_MIN_GLPI", "11.0.0");
define("PLUGIN_GAPPESSENTIALS_MAX_GLPI", "11.0.99");
define("PLUGIN_GAPPESSENTIALS_ICON", "fa-solid fa-e");

/**
 * Init hooks of the plugin.
 * REQUIRED
 *
 * @return void
 */
function plugin_init_gappessentials()
{
   global $PLUGIN_HOOKS;

   $plugin = new Plugin();
	if ($plugin->isActivated('gappessentials')) {
      Plugin::registerClass('PluginGappessentialsConfig', ['addtabon' => 'Config']);
      $PLUGIN_HOOKS[Hooks::CONFIG_PAGE]['gappessentials'] = 'front/config.form.php';
   }
}

/**
 * Get the name and the version of the plugin
 * REQUIRED
 *
 * @return array
 */
function plugin_version_gappessentials()
{
   return [
      'name'           => 'Gapp Essentials',
      'version'        => PLUGIN_GAPPESSENTIALS_VERSION,
      'author'         => '<a href="https://tic.gal">TICGAL</a>',
      'license'        => 'AGPLv3+',
      'homepage'       => 'https://tic.gal/en/gappessentials/',
      'requirements'   => [
         'glpi' => [
            'min' => PLUGIN_GAPPESSENTIALS_MIN_GLPI,
            'max' => PLUGIN_GAPPESSENTIALS_MAX_GLPI,
         ]
      ]
   ];
}
