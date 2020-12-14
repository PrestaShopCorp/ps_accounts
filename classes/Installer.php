<?php


namespace PrestaShop\Module\PsAccounts;


class Installer
{
    /**
     * @return string | null
     *
     * @throws \PrestaShopException
     */
    public function getPsAccountsInstallLink()
    {
        if (true === Module::isInstalled('ps_accounts')) {
            return null;
        }

        if ($this->shopContext->isShop17()) {
            $router = $this->get('router');

            return Tools::getHttpHost(true) . $router->generate('admin_module_manage_action', [
                    'action' => 'install',
                    'module_name' => 'ps_accounts',
                ]);
        }

        return  $this->link->getAdminLink('AdminModules', true, [], [
            'module_name' => $this->psxName,
            'configure' => $this->psxName,
            'install' => 'ps_accounts',
        ]);
    }

    /**
     * @return string | null
     *
     * @throws \PrestaShopException
     */
    public function getPsAccountsEnableLink()
    {
        if (true === Module::isEnabled('ps_accounts')) {
            return null;
        }

        if ($this->shopProvider->getShopContext()->isShop17()) {
            $router = $this->get('router');

            return Tools::getHttpHost(true) . $router->generate('admin_module_manage_action', [
                    'action' => 'enable',
                    'module_name' => 'ps_accounts',
                ]);
        }

        return  $this->link->getAdminLink('AdminModules', true, [], [
            'module_name' => $this->psxName,
            'configure' => $this->psxName,
            'enable' => 'ps_accounts',
        ]);
    }
}
