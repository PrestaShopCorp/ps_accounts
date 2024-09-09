<?php
namespace PrestaShop\Module\PsAccounts\Controller\Admin;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;

class LoginController extends FrameworkBundleAdminController
{
    public function loginPage()
    {
        return $this->render('@Modules/ps_accounts/templates/admin/login.html.twig');
    }
}
