<?php

/**
 *  -------------------------------------------------------------------------
 *  GappEssentials plugin for GLPI
 *  Copyright (C) 2019 - 2026 by TICGAL.
 *  https://tic.gal
 *  https://github.com/pluginsGLPI/gappessentials
 *  -------------------------------------------------------------------------
 *
 *  LICENSE
 *
 *  This file is part of GappEssentials.
 *
 *  GappEssentials is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  GappEssentials is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with GappEssentials. If not, see <http://www.gnu.org/licenses/>.
 *  --------------------------------------------------------------------------
 *  * @package   gappessentials
 *  * @author    the TICGAL team
 *  * @copyright Copyright (C) 2019 - 2026 TICGAL team
 *  * @license   AGPL License 3.0 or (at your option) any later version
 *  *            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 *  * @link      https://www.tic.gal
 *  * @since     2019
 *  * -------------------------------------------------------------------------
 */

// This file contains stubs for GLPI constants.
// Please try to keep them alphabetically ordered.

// Directories constants
define('GLPI_CACHE_DIR', null);
define('GLPI_CRON_DIR', null);
define('GLPI_DOC_DIR', null);
define('GLPI_DUMP_DIR', null);
define('GLPI_GRAPH_DIR', null);
define('GLPI_INVENTORY_DIR', null);
define('GLPI_LOCAL_I18N_DIR', null);
define('GLPI_LOCK_DIR', null);
define('GLPI_LOG_DIR', null);
define('GLPI_MARKETPLACE_DIR', null);
define('GLPI_PICTURE_DIR', null);
define('GLPI_PLUGIN_DOC_DIR', null);
define('GLPI_RSS_DIR', null);
define('GLPI_SESSION_DIR', null);
define('GLPI_TMP_DIR', null);
define('GLPI_UPLOAD_DIR', null);
define('GLPI_VAR_DIR', null);

// Optionnal constants
define('GLPI_FORCE_MAIL', null);
define('GLPI_LOG_LVL', null);
define('GLPI_SQL_DEBUG', null);
define('GLPI_STRICT_DEPRECATED', null);

// Other constants
define('GLPI_AJAX_DASHBOARD', null);
define('GLPI_ALLOW_IFRAME_IN_RICH_TEXT', null);
define('GLPI_CALDAV_IMPORT_STATE', null);
define('GLPI_CSRF_EXPIRES', null);
define('GLPI_CSRF_MAX_TOKENS', null);
define('GLPI_DEMO_MODE', null);
define('GLPI_DISABLE_ONLY_FULL_GROUP_BY_SQL_MODE', null);
define('GLPI_IDOR_EXPIRES', null);
define('GLPI_INSTALL_MODE', null);
define('GLPI_MARKETPLACE_ALLOW_OVERRIDE', null);
define('GLPI_MARKETPLACE_ENABLE', null);
define('GLPI_MARKETPLACE_MANUAL_DOWNLOADS', null);
define('GLPI_MARKETPLACE_PLUGINS_API_URI', null);
define('GLPI_MARKETPLACE_PRERELEASES', null);
define('GLPI_NETWORK_REGISTRATION_API_URL', null);
define('GLPI_NETWORK_MAIL', null);
define('GLPI_NETWORK_SERVICES', null);
define('GLPI_SERVERSIDE_URL_ALLOWLIST', null);
define('GLPI_TELEMETRY_URI', null);
define('GLPI_USE_CSRF_CHECK', null);
define('GLPI_USE_IDOR_CHECK', null);
define('GLPI_USER_AGENT_EXTRA_COMMENTS', null);
define('GLPI_CENTRAL_WARNINGS', null);
define('GLPI_TEXT_MAXSIZE', null);

// GLPI access rights constants
define('READ', 1);
define('UPDATE', 2);
define('CREATE', 4);
define('DELETE', 8);
define('PURGE', 16);
define('ALLSTANDARDRIGHT', 31);
define('READNOTE', 32);
define('UPDATENOTE', 64);
define('UNLOCK', 128);

// GLPI notification constants
define('ERROR', 1);
define('WARNING', 2);
define('INFO', 4);

// GLPI time constants
define('MINUTE_TIMESTAMP', 60);
define('HOUR_TIMESTAMP', 3600);
define('DAY_TIMESTAMP', 86400);
define('WEEK_TIMESTAMP', 604800);
define('MONTH_TIMESTAMP', 2592000);
