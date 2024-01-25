<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

namespace PrestaShop\Module\PsAccounts\Module;

use Tools;

class Install
{
    const PARENT_TAB_NAME = -1;
    const TAB_ACTIVE = 0;

    /**
     * @var \Ps_accounts
     */
    private $module;

    /**
     * @var \Db
     */
    private $db;

    public function __construct(\Ps_accounts $module, \Db $db)
    {
        $this->module = $module;
        $this->db = $db;
    }

    /**
     * installInMenu.
     *
     * @return bool
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function installInMenu()
    {
        foreach ($this->module->getAdminControllers() as $controllerName) {
            $tabId = (int) \Tab::getIdFromClassName($controllerName);

            if (!$tabId) {
                $tabId = null;
            }

            $tab = new \Tab($tabId);
            $tab->active = (bool) self::TAB_ACTIVE;
            $tab->class_name = $controllerName;
            $tab->name = [];

            /** @var array $lang */
            foreach (\Language::getLanguages(true) as $lang) {
                $tab->name[$lang['id_lang']] = $controllerName . ' (' . $this->module->displayName . ')';
            }

            $tab->id_parent = -1 == self::PARENT_TAB_NAME ? (int) \Tab::getIdFromClassName((string) self::PARENT_TAB_NAME) : -1;
            $tab->module = $this->module->name;

            $tab->save();
        }

        return true;
    }

    /**
     * Installs database tables
     *
     * @return bool
     */
    public function installDatabaseTables()
    {
        // run iterative database migrations
        return $this->runMigration('create_employee_account_table');
    }

    /**
     * @param string $migration
     *
     * @return bool
     */
    public function runMigration($migration)
    {
        $dbInstallFile = "{$this->module->getLocalPath()}/sql/{$migration}.sql";

        if (!file_exists($dbInstallFile)) {
            return false;
        }

        $sql = Tools::file_get_contents($dbInstallFile);

        if (empty($sql) || !is_string($sql)) {
            return false;
        }

        $sql = str_replace(['PREFIX_', 'ENGINE_TYPE'], [_DB_PREFIX_, _MYSQL_ENGINE_], $sql);
        $sql = preg_split("/;\s*[\r\n]+/", trim($sql));

        if (!empty($sql)) {
            foreach ($sql as $query) {
                if (!$this->db->execute($query)) {
                    return false;
                }
            }
        }

        return true;
    }
}
