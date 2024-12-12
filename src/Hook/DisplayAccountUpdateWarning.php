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

use PrestaShop\Module\PsAccounts\Service\PsAccountsService;

class DisplayAccountUpdateWarning extends Hook
{
    /**
     * @param array $params
     *
     * @return string
     */
    public function execute(array $params = [])
    {
        /** @var PsAccountsService $accountsService */
        $accountsService = $this->module->getService(PsAccountsService::class);

        if ($accountsService->isAccountLinked() &&
            !$this->module->getShopContext()->isMultishopActive()) {
            $msg = $this->module->l(
                'This shop is linked to your PrestaShop account. ' .
                'Unlink your shop if you do not want to impact your live settings.',
                'ps_accounts'
            );

            return <<<HTML
<div class="row">
  <div class="col-sm">
    <div class="alert alert-warning" role="alert">
      <div class="alert-text">
        $msg
      </div>
    </div>
  </div>
</div>
HTML;
        }

        return '';
    }
}
