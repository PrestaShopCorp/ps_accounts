<?php
/**
 * 2007-2020 PrestaShop and Contributors.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace PrestaShop\Module\PsAccounts\Module;

class Install
{
    const PARENT_TAB_NAME = -1;
    const TAB_ACTIVE = 0;

    /**
     * @var \Ps_accounts
     */
    private $module;

    public function __construct(\Ps_accounts $module)
    {
        $this->module = $module;
    }

    /**
     * installInMenu.
     *
     * @return bool
     */
    public function installInMenu()
    {
        foreach ($this->module->adminControllers as $controllerName) {
            $tabId = (int) \Tab::getIdFromClassName($controllerName);

            if (!$tabId) {
                $tabId = null;
            }

            $tab = new \Tab($tabId);
            $tab->active = (bool) self::TAB_ACTIVE;
            $tab->class_name = $controllerName;
            $tab->name = [];

            foreach (\Language::getLanguages(true) as $lang) {
                $tab->name[$lang['id_lang']] = $this->module->displayName;
            }

            $tab->id_parent = -1 == self::PARENT_TAB_NAME ? (int) \Tab::getIdFromClassName((string) self::PARENT_TAB_NAME) : -1;
            $tab->module = $this->module->name;

            $tab->save();
        }

        return true;
    }
}
