<?php
/**
 * 2007-2019 PrestaShop and Contributors.
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
 * @copyright 2007-2019 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace PrestaShop\Module\PsAccounts\Module;

class Install
{
    const PARENT_TAB_NAME = 'IMPROVE';

    private $module;

    public function __construct(\Module $module)
    {
        $this->module = $module;
    }

    /**
     * installInMenu.
     */
    public function installInMenu()
    {
        $tabId = (int) \Tab::getIdFromClassName($this->module->adminControllers['configure']);

        if (!$tabId) {
            $tabId = null;
        }

        $tab = new \Tab($tabId);
        $tab->active = 1;
        $tab->class_name = $this->module->adminControllers['configure'];
        $tab->name = array();

        foreach (\Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = $this->module->displayName;
        }

        $tab->id_parent = (int) \Tab::getIdFromClassName(self::PARENT_TAB_NAME);
        $tab->module = $this->module->name;

        return $tab->save();
    }
}
