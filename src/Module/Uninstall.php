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

class Uninstall
{
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
     * uninstallMenu.
     *
     * @return bool
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function uninstallMenu()
    {
        // foreach( ['configure', 'hmac', 'ajax'] as $aliasController){
        foreach ($this->module->getAdminControllers() as $controllerName) {
            $tabId = (int) \Tab::getIdFromClassName($controllerName);

            if (!$tabId) {
                return true;
            }

            $tab = new \Tab($tabId);

            return $tab->delete();
        }

        return true;
    }

    /**
     * @param string $controllerName
     *
     * @return void
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function deleteAdminTab($controllerName)
    {
        $tabId = (int) \Tab::getIdFromClassName($controllerName);
        if ($tabId) {
            (new \Tab($tabId))->delete();
        }
    }

    /**
     * @return bool
     */
    public function uninstallDatabaseTables()
    {
        $dbUninstallFile = "{$this->module->getLocalPath()}/sql/uninstall.sql";

        if (!file_exists($dbUninstallFile)) {
            return false;
        }

        $sql = Tools::file_get_contents($dbUninstallFile);

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
