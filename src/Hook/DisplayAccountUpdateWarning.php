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

namespace PrestaShop\Module\PsAccounts\Hook;

use Exception;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;

class DisplayAccountUpdateWarning extends Hook
{
    /**
     * @var PsAccountsService
     */
    private $accountsService;

    public function __construct(\Ps_accounts $module)
    {
        parent::__construct($module);
        $this->accountsService = $this->module->getService(PsAccountsService::class);
    }

    /**
     * @return string
     *
     * @throws Exception
     */
    public function execute(array $params = [])
    {
        if ($this->accountsService->isAccountLinked() &&
            !$this->module->getShopContext()->isMultishopActive()) {
            // I don't load with $this->get('twig') since i had this error https://github.com/PrestaShop/PrestaShop/issues/20505
            // Some users may have the same and couldn't render the configuration page
            return $this->module->renderUpdateWarningView();
        }

        return '';
    }
}
