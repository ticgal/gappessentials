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

namespace GlpiPlugin\Gappessentials;

use CommonDBTM;
use CommonGLPI;
use Glpi\Application\View\TemplateRenderer;
use Migration;
use Plugin;
use Session;

class Config extends CommonDBTM
{
    public static $rightname = 'config';

    public static function canCreate(): bool
    {
        return Session::haveRight('config', UPDATE);
    }

    public static function canView(): bool
    {
        return Session::haveRight('config', READ);
    }

    public static function canUpdate(): bool
    {
        return Session::haveRight('config', UPDATE);
    }

    public static function getTypeName($nb = 0)
    {
        return __('Gapp Essentials', 'gappessentials');
    }

    public static function getIcon()
    {
        return PLUGIN_GAPPESSENTIALS_ICON;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item->getType() === 'Config') {
            return self::createTabEntry(self::getTypeName());
        }

        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item->getType() === 'Config') {
            self::showConfigForm();
        }

        return true;
    }

    public static function showConfigForm(): void
    {
        $config = new self();
        $config->getFromDB(1);

        TemplateRenderer::getInstance()->display('@gappessentials/config.html.twig', [
            'item'                 => $config,
            'can_update'           => self::canUpdate(),
            'label_request_source' => __('Request source', 'gappessentials'),
        ]);
    }

    public static function install(Migration $migration): void
    {
        /** @var \DBmysql $DB */
        global $DB;

        $table = self::getTable();
        if (!$DB->tableExists($table)) {
            $migration->displayMessage("Installing $table");
            $DB->runFile(Plugin::getPhpDir('gappessentials') . '/install/mysql/empty.sql');
            (new self())->add([
                'id'              => 1,
                'requesttypes_id' => 0,
            ]);
        }
    }

    public static function uninstall(Migration $migration): void
    {
        $migration->displayMessage('Uninstalling ' . self::getTable());
        $migration->dropTable(self::getTable());
    }
}
