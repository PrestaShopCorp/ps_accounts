<?php
/**
 * @param Ps_accounts $module
 *
 * @return bool
 *
 * @throws Exception
 * @throws Throwable
 */
function upgrade_module_7_0_0($module)
{
    foreach ([
                 //'displayBackOfficeHeader',
                 //'actionAdminLoginControllerSetMedia',
                 'actionAdminControllerInitBefore',
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

    $currentShopId = $conf->getShopId();
    foreach (get_shops_7_0_0($conf->isMultishopActive()) as $id) {
        if ($id !== null) {
            $conf->setShopId($id);
        }
        $shopUuid = $conf->getShopUuid();
        $shopToken = $conf->getFirebaseIdToken();

        // Trigger update for linked shop only
        if ($shopUuid && $shopToken) {
            update_shop_module_7_0_0(
                $module->getParameter('ps_accounts.accounts_api_url') . 'v2/shop/module/update',
                $shopUuid,
                $shopToken,
                [
                    'version' => \Ps_accounts::VERSION,
                ],
                (bool) $module->getParameter('ps_accounts.check_api_ssl_cert')
            );
        }
    }
    $conf->setShopId($currentShopId);

    return true;
}

/**
 * @param $multishop
 *
 * @return array|null[]
 *
 * @throws PrestaShopDatabaseException
 */
function get_shops_7_0_0($multishop)
{
    $shops = [null];
    if ($multishop) {
        $shops = [];
        $db  = \Db::getInstance();
        $result = $db->query('SELECT id_shop FROM ' . _DB_PREFIX_ . 'shop');
        while ($row = $db->nextRow($result)) {
            $shops[] = $row['id_shop'];
        }
    }
    return $shops;
}

/**
 * @param string $uri
 * @param string $shopUid
 * @param string $shopToken
 * @param array $data
 * @param bool $verify
 *
 * @return array
 */
function update_shop_module_7_0_0($uri, $shopUid, $shopToken, $data, $verify)
{
    $formData = http_build_query($data);

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
