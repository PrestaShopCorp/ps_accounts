<?php

namespace PrestaShop\Module\PsAccounts\Controller\Admin;

//use PrestaShopBundle\Controller\Admin\PrestaShopAdminController;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Bundle\SecurityBundle\Security;

class OAuth2Controller extends FrameworkBundleAdminController
{
    /**
     * @var Security
     */
    private $security;

    public function __construct()
    {
        //$this->security = $security;
    }

    public function initOAuth2Flow()
    {
        /** @var Security $security */
        $security = $this->container->get('security.helper');
    }
}
