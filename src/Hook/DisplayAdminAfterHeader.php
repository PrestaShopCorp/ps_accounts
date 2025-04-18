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

use PrestaShop\Module\PsAccounts\Account\Exception\RefreshTokenException;
use PrestaShop\Module\PsAccounts\Account\Exception\UnknownStatusException;
use PrestaShop\Module\PsAccounts\Account\StatusManager;
use PrestaShop\Module\PsAccounts\Service\Accounts\AccountsException;

class DisplayAdminAfterHeader extends Hook
{
    /**
     * @return string
     */
    public function execute(array $params = [])
    {
        /** @var StatusManager $statusManager */
        $statusManager = $this->module->getService(StatusManager::class);

        try {
            if ($statusManager->getStatus()->isVerified) {
                return '';
            }
        } catch (UnknownStatusException $e) {
        } catch (RefreshTokenException $e) {
        } catch (AccountsException $e) {
        }

        return <<<HTML
<div class="bootstrap">
    <div class="alert alert-warning">
        <button type="button" class="close" data-dismiss="alert">×</button>
        <!-- img width="57" alt="PrestaShop Account" title="PrestaShop Account" src="/modules/ps_accounts/logo.png"-->
        Your shop has not been verified : <a>{$statusManager->getCloudShopId()}</a>
    </div>
</div>
HTML;
    }
}
