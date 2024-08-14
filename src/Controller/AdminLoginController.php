<?php
namespace PrestaShop\Module\PsAccounts\Controller;

use Doctrine\Common\Cache\CacheProvider;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;

class AdminLoginController extends FrameworkBundleAdminController
{
    /**
     * @var CacheProvider
     */
    private $cache;

    // you can use symfony DI to inject services
    //public function __construct(CacheProvider $cache)
    public function __construct()
    {
        //$this->cache = $cache;
        $this->cache = $this->container->get('doctrine.cache');
    }

    public function loginPage()
    {
        return $this->render('@Modules/ps_accounts/templates/admin/login.html.twig');
    }
}
