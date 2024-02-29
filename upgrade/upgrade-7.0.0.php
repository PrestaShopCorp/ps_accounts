<?php
/**
 * @param Ps_accounts $module
 *
 * @return bool
 *
 * @throws Exception
 */
function upgrade_module_7_0_0($module)
{
    foreach ([
                 //'displayBackOfficeHeader',
                 //'actionAdminLoginControllerSetMedia',
                 'actionAdminControllerInitBefore',
                 'actionObjectShopAddAfter',
                 'actionObjectShopDeleteAfter',
                 'actionModuleInstallAfter',
             ] as $hook) {
        $module->unregisterHook($hook);
    }
    $module->addCustomHooks($module->getCustomHooks());
    $module->registerHook($module->getHooksToRegister());

    // FIXME: this wont prevent from re-implanting override on reset of module
    $uninstaller = new PrestaShop\Module\PsAccounts\Module\Uninstall($module, Db::getInstance());
    $uninstaller->deleteAdminTab('AdminLogin');

    $installer = new PrestaShop\Module\PsAccounts\Module\Install($module, Db::getInstance());
    $installer->installInMenu();

    /** @var \PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository $conf */
    $conf = $module->getService(\PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository::class);

    updateShopModule_7_0_0(
        $module->getParameter('ps_accounts.accounts_api_url') . 'v2/shop/module/update',
        $conf->getShopUuid(),
        $conf->getFirebaseIdToken(),
        [
            'version' => \Ps_accounts::VERSION,
        ]
    );

    return true;
}

function updateShopModule_7_0_0($uri, $shopUid, $shopToken, $data)
{
    $formData = http_build_query($data);
    $verify = false;

    return json_decode(
        file_get_contents(
            $uri,
            false,
            stream_context_create([
                'ssl' => [
                    'verify_peer' => $verify,
                    'verify_peer_name' => $verify,
                ],
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n" .
                        'Content-Length: ' . strlen($formData) . "\r\n" .
                        "Authorization: Bearer $shopToken\r\n" .
                        "X-Shop-Id: $shopUid\r\n",
                    'content' => $formData,
                    'ignore_errors' => '1',
                ],
            ])
        ),
        true
    ) ?: [];
}
