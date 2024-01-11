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

if (!defined('GLPI_ROOT')) {
	die("Sorry. You can't access directly to this file");
}

class PluginGappessentialsConfig extends CommonDBTM
{
	static private $_instance = null;

	public function __construct()
	{
		global $DB;
		if ($DB->tableExists($this->getTable())) {
			$this->getFromDB(1);
		}
	}

	static function canCreate()
	{
		return Session::haveRight('config', UPDATE);
	}

	static function canView()
	{
		return Session::haveRight('config', READ);
	}

	static function canUpdate()
	{
		return Session::haveRight('config', UPDATE);
	}

	static function getTypeName($nb = 0)
	{
		return "Gapp Essentials";
	}

	static function getInstance()
	{
		if (!isset(self::$_instance)) {
			self::$_instance = new self();
			if (!self::$_instance->getFromDB(1)) {
				self::$_instance->getEmpty();
			}
		}
		return self::$_instance;
	}

	static function getConfig($update = false)
	{
		static $config = null;
		if (is_null(self::$config)) {
			$config = new self();
		}
		if ($update) {
			$config->getFromDB(1);
		}
		return $config;
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
			return self::getTypeName();
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
			$DB->query($query) or die($DB->error());
			$config->add([
				'id' => 1,
				'requesttypes_id' => 0,
			]);
		}
	}
}
