<?php

/**
 * @param Ps_accounts $module
 *
 * @return bool
 *
 * @throws Exception
 */
function upgrade_module_5_2_0($module)
{
    $module->addCustomHooks($module->getCustomHooks());
    $module->registerHook($module->getHookToInstall());

    updateShopUrl($module);

    return true;
}

function updateShopUrl($module)
{
    /** @var \PrestaShop\Module\PsAccounts\Provider\ShopProvider $shopProvider */
    $shopProvider = $module->getService(\PrestaShop\Module\PsAccounts\Provider\ShopProvider::class);

    /** @var \PrestaShop\Module\PsAccounts\Context\ShopContext $shopContext */
    $shopContext = $module->getService(\PrestaShop\Module\PsAccounts\Context\ShopContext::class);

    /** @var \PrestaShop\Module\PsAccounts\Service\PsAccountsService $accountsService */
    $accountsService = $module->getService(\PrestaShop\Module\PsAccounts\Service\PsAccountsService::class);

    /** @var \PrestaShop\Module\PsAccounts\Api\Client\AccountsClient $accountsApi */
    $accountsApi = $module->getService(
        \PrestaShop\Module\PsAccounts\Api\Client\AccountsClient::class
    );

    $shopsTree = $shopProvider->getShopsTree('ps_accounts');

    foreach ($shopsTree as $shopGroup) {
        foreach ($shopGroup['shops'] as $shop) {
            $shopContext->execInShopContext($shop['id'], function () use ($accountsService, $shop, $accountsApi, $module) {
                try {
                    if ($accountsService->isAccountLinked()) {
                        $response = $accountsApi->updateUserShop(new \PrestaShop\Module\PsAccounts\DTO\UpdateShop([
                            'shopId' => (string) $shop['id'],
                            'name' => $shop['name'],
                            'domain' => 'http://' . $shop['domain'],
                            'sslDomain' => 'https://' . $shop['domainSsl'],
                            'physicalUri' => $shop['physicalUri'],
                            // FIXME when we have the virtual uri in tree, add it here
                            'virtualUri' => '',
                            'boBaseUrl' => $shop['url'],
                        ]));

                        if (!$response || true !== $response['status']) {
                            $module->getLogger()->debug(
                                'Error trying to PATCH shop : ' . $response['httpCode'] .
                                ' ' . print_r($response['body']['message'], true)
                            );
                        }
                    }
                } catch (\Exception $e) {
                    $module->getLogger()->debug(
                        'Error while trying to PATCH shop : ' . $e->getMessage(), true
                    );
                }
            });
        }
    }
}
