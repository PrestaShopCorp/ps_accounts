<?php

namespace PrestaShop\Module\PsAccounts\Tests\Feature\Account\CommandHandler;

use PrestaShop\Module\PsAccounts\Adapter\ConfigurationKeys;
use PrestaShop\Module\PsAccounts\Adapter\Link;
use PrestaShop\Module\PsAccounts\Tests\Feature\TestCase;
use PrestaShop\Module\PsAccounts\Vendor\GuzzleHttp\Cookie\CookieJar;

class UpgradeModuleHandlerTest extends TestCase
{
    /**
     * @inject
     *
     * @var Link
     */
    protected $link;

    /**
     * @var CookieJar
     */
    private $cookieJar;

    public function set_up()
    {
        parent::set_up();

        $this->markTestSkipped('Implement cookie management with cURL');

        if (version_compare(_PS_VERSION_, '1.7.0.0', '<') ||
            version_compare(_PS_VERSION_, '9', '>=')) {
            $this->markTestSkipped('Login test compatible with 1.7 & 8 only');
        }

        $this->cookieJar = new CookieJar();
    }

    /**
     * @test
     */
    public function itShouldUpdateModuleVersion()
    {
        $this->setVersion($this->incrementVersion(\Ps_accounts::VERSION, -1));

        $this->assertResponseOk(
            $this->loginIntoBackoffice()
        );

        $this->assertEquals(\Ps_accounts::VERSION, $this->getVersion());
    }

    /**
     * @test
     */
    public function itShouldNotUpdateModuleVersion()
    {
        $incrementedVersion = $this->incrementVersion(\Ps_accounts::VERSION, +1);

        $this->setVersion($incrementedVersion);

        $this->assertResponseOk(
            $this->loginIntoBackoffice()
        );

        $this->assertEquals($incrementedVersion, $this->getVersion());
    }

//    /**
//     * @test
//     */
//    public function itShouldUpdateModuleVersionOnlyOnce()
//    {
//        $this->setVersion('7.0.1');
//
//        $response = $this->loginIntoBackoffice();
//
//        $json = $this->getResponseJson($response);
//
//        $conf = $this->getUncachedConfiguration(ConfigurationKeys::PS_ACCOUNTS_LAST_UPGRADE);
//
//        $this->assertEquals(\Ps_accounts::VERSION, $conf->value);
//
//        $t1 = $conf->date_upd;
//        echo "T1: " . $t1 . "(" . $conf->date_add . ")\n";
//
//        sleep(5);
//
//        $this->displayBackofficePage($json['redirect'], $this->cookieJar);
//
//        $conf2 = $this->getUncachedConfiguration(ConfigurationKeys::PS_ACCOUNTS_LAST_UPGRADE);
//
//        $this->assertEquals(\Ps_accounts::VERSION, $conf2->value);
//
//        $t2 = $conf2->date_upd;
//        echo "T1: " . $t2 . "(" . $conf2->date_add . ")\n";
//
//        $this->assertEquals($t1, $t2);
//    }

    /**
     * @param string $version
     * @param int|null $idGroup
     * @param int|null $idShop
     *
     * @return void
     */
    protected function setVersion($version, $idGroup = null, $idShop = null)
    {
        //$this->configurationRepository->updateLastUpgrade($version);
        $this->configuration->setRaw(ConfigurationKeys::PS_ACCOUNTS_LAST_UPGRADE, $version, false, $idGroup, $idShop);
    }

    /**
     * @param int|null $idGroup
     * @param int|null $idShop
     *
     * @return string
     */
    protected function getVersion($idGroup = null, $idShop = null)
    {
        //return $this->configurationRepository->getLastUpgrade();
        return $this->configuration->getUncached(ConfigurationKeys::PS_ACCOUNTS_LAST_UPGRADE, $idGroup, $idShop);
    }

    /**
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function loginIntoBackoffice()
    {
        $jar = $this->cookieJar;

        $resLoginPage = $this->displayLoginPage($jar);

        $this->module->getLogger()->info(print_r($resLoginPage->getBody(), true));

        $this->assertResponseOk(
            $this->displayLoginPage($jar)
        );

        $res = $this->postLogionForm([
            'ajax' => 1,
            //'token' => '',
            'controller' => 'AdminLogin',
            'submitLogin' => 1,
            'email' => 'admin@prestashop.com',
            'passwd' => 'prestashop',
            //'redirect' => '/admin-dev/index.php?controller=AdminDashboard',
        ], $jar);
        $this->assertResponseOk($res);

        $json = $this->getResponseJson($res);

        $this->assertResponseOk(
            $this->displayBackofficePage($json['redirect'], $jar)
        );

        return $res;
    }

    /**
     * @param CookieJar $jar
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function displayLoginPage(CookieJar $jar)
    {
        return $this->client->get('/admin-dev/index.php?controller=AdminLogin', [
            'cookies' => $jar,
        ]);
    }

    /**
     * @param CookieJar $jar
     * @param array $form
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function postLogionForm(array $form, CookieJar $jar)
    {
        return $this->client->post('/admin-dev/index.php?rand=' . time(), [
            'form_params' => $form,
            'cookies' => $jar,
        ]);
    }

    /**
     * @param string $redirect
     * @param CookieJar $jar
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function displayBackofficePage($redirect, CookieJar $jar)
    {
        return $this->client->get($redirect, [
            'cookies' => $jar,
        ]);
    }

    /**
     * @param string $version
     * @param int $increment
     *
     * @return string
     */
    protected function incrementVersion($version, $increment)
    {
        list($major, $minor, $patch) = explode('.', $version);

        if ($increment > 0) {
            return $major . '.' . $minor . '.' . ($patch + 1);
        } else {
            foreach (['patch', 'minor', 'major'] as $part) {
                if ($$part -1 > 0) {
                    $$part -= 1;
                    break;
                }
            }
            return $major . '.' . $minor . '.' . $patch;
        }
    }

    /**
     * @param string $key
     * @param int|null $idShopGroup
     * @param int|null $idShop
     * @param mixed $default
     *
     * @return \Configuration
     *
     * @throw \Exception
     */
    protected function getUncachedConfiguration($key, $idShopGroup = null, $idShop = null, $default = false)
    {
        $id = \Configuration::getIdByName($key, $idShopGroup, $idShop);
        if ($id > 0) {
            $found = (new \Configuration($id));
            $found->clearCache();

            return $found;
        }

        throw new \Exception('Configuration entry not found');
    }
}
