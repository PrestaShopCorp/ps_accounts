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

use PrestaShop\Module\PsAccounts\Account\Exception\UnknownStatusException;
use PrestaShop\Module\PsAccounts\Account\StatusManager;
use PrestaShop\Module\PsAccounts\Adapter\Link;
use PrestaShop\Module\PsAccounts\Log\Logger;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;

class DisplayAdminAfterHeader extends Hook
{
    /**
     * @return string
     */
    public function execute(array $params = [])
    {
        $e = null;

        try {
            // TODO: check if module is correctly installed
            //$tabId = (int) \Tab::getIdFromClassName('AdminAjaxV2PsAccountsController');
            //return $this->displayUrlMismatchWarning();

        } catch (UnknownStatusException $e) {
        } catch (\Exception $e) {
        } catch (\Throwable $e) {
        }

        if ($e) {
            Logger::getInstance()->error('error rendering hook : ' . $e->getMessage());
        }
        return '';
    }

    /**
     * @return string
     *
     * @throws UnknownStatusException
     */
    private function displayUrlMismatchWarning()
    {
        /** @var PsAccountsService $psAccountsService */
        $psAccountsService = $this->module->getService(PsAccountsService::class);

        if (preg_match('/controller=AdminModules/', $_SERVER['REQUEST_URI']) &&
            preg_match('/configure=ps_accounts/', $_SERVER['REQUEST_URI']) ||
            preg_match('@modules/manage/action/configure/ps_accounts@', $_SERVER['REQUEST_URI'])
        ) {
            return '';
        }

        if (!$psAccountsService->isShopIdentityCreated()) {
            return '';
        }

        /** @var StatusManager $statusManager */
        $statusManager = $this->module->getService(StatusManager::class);

        /** @var ShopProvider $shopProvider */
        $shopProvider = $this->module->getService(ShopProvider::class);

        $shopUrl = $shopProvider->getUrl((int) \Context::getContext()->shop->id);

        $status = $statusManager->getStatus();

        $cloudFrontendURL = $status->frontendUrl;
        $localFrontendURL = $shopUrl->getFrontendUrl();

        if ($cloudFrontendURL !== $localFrontendURL) {
            /** @var Link $link */
            $link = $this->module->getService(Link::class);
            $moduleLink = $link->getAdminLink('AdminModules', true, [], [
                'configure' => 'ps_accounts',
            ]);
            //$healthCheckLink = $link->getLink()->getModuleLink('ps_accounts', 'apiV2ShopHealthCheck');

//            $msg = $this->module->l(
//                'This shop is linked to your PrestaShop account. ' .
//                'Unlink your shop if you do not want to impact your live settings.',
//                'ps_accounts'
//            );

            return
                <<<HTML
<div class="bootstrap">
    <div class="alert alert-danger alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">×</button>
        We detected a change in your shop URL.<br />
        <ul>
            <li>PrestaShop Account URL&nbsp;: <em>{$cloudFrontendURL}</em></li>
            <li>Your Shop URL&nbsp;: <em>{$localFrontendURL}</em></li>
        </ul>
        Please review your <a href="{$moduleLink}">PrestaShop Account settings</a>
    </div>
</div>
HTML;
        }
        return '';
    }
}
