<?php
/*
 -------------------------------------------------------------------------
 GappEssentials plugin for GLPI
 Copyright (C) 2019 by the TICgal
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
 */

use Glpi\Cache\CacheManager;

define('GLPI_ROOT', substr(__DIR__, 0, (strpos(__DIR__, "marketplace") - 1)));
define('DO_NOT_CHECK_HTTP_REFERER', 1);
ini_set('session.use_cookies', 0);

include_once(GLPI_ROOT . "/inc/based_config.php");
include_once(GLPI_ROOT . "/marketplace/gappessentials/inc/apirest.class.php");

// Init loggers
$GLPI = new GLPI();
$GLPI->initLogger();
$GLPI->initErrorHandler();

//init cache
$cache_manager = new CacheManager();
$GLPI_CACHE = $cache_manager->getCoreCacheInstance();

$api = new PluginGappEssentialsApirest();
$api->call();
