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

if (!defined('GLPI_ROOT')) {
	die("Sorry. You can't access directly to this file");
}

class PluginGappessentialsConfig extends CommonDBTM
{
    public static $rightname = 'config';

    private static ?self $instance = null;

	public function __construct()
	{
		global $DB;
		if ($DB->tableExists($this->getTable())) {
			$this->getFromDB(1);
		}
	}

	static function canCreate(): bool
	{
		return Session::haveRight('config', UPDATE);
	}

	static function canView(): bool
	{
		return Session::haveRight('config', READ);
	}

	static function canUpdate(): bool
	{
		return Session::haveRight('config', UPDATE);
	}

	static function getTypeName($nb = 0)
	{
		return "Gapp Essentials";
	}

	public static function getInstance(int $n = 1)
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
            if (!self::$instance->getFromDB($n)) {
                self::$instance->getEmpty();
            }
        }

        return self::$instance;
    }

	static function showConfigForm()
	{

		$config = new self();
		$config->getFromDB(1);

		$config->showFormHeader(['colspan' => 1]);
		echo "<tr class='tab_bg_1'>";
		echo "<td>" . __("Request source") . "</td><td>";

		$condition = ['is_active' => 1, 'is_ticketheader' => 1];
		RequestType::dropdown(['value' => $config->fields["requesttypes_id"], 'condition' => $condition]);
		echo "</td>";
		echo "</tr>";

		$config->showFormButtons(['candel' => false]);

		return false;
	}

	function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
	{
		if ($item->getType() == 'Config') {
			return self::createTabEntry(self::getTypeName());
		}
		return '';
	}

	static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
	{
		if ($item->getType() == 'Config') {
			self::showConfigForm($item);
		}
		return true;
	}

    public static function getIcon()
    {
        return PLUGIN_GAPPESSENTIALS_ICON;
    }

	static function install(Migration $migration)
	{
		global $DB;

		$default_charset = DBConnection::getDefaultCharset();
		$default_collation = DBConnection::getDefaultCollation();
		$default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

		$table = self::getTable();
		$config = new self();
		if (!$DB->tableExists($table)) {
			$migration->displayMessage("Installing $table");
			$query = "CREATE TABLE IF NOT EXISTS $table (
				`id` int {$default_key_sign} NOT NULL auto_increment,
				`requesttypes_id` int {$default_key_sign} NOT NULL default '0',
				PRIMARY KEY (`id`)
				) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
			$DB->doQuery($query);
			$config->add([
				'id' => 1,
				'requesttypes_id' => 0,
			]);
		}
	}
}
