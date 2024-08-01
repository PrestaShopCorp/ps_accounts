<?php

namespace PrestaShop\Module\PsAccounts\Tests\Feature\Api\v1;

use PrestaShop\Module\PsAccounts\Adapter\ConfigurationKeys;
use PrestaShop\Module\PsAccounts\Adapter\Link;
use PrestaShop\Module\PsAccounts\Tests\Feature\FeatureTestCase;
use PrestaShop\Module\PsAccounts\Vendor\GuzzleHttp\Cookie\CookieJar;

class DecodePayloadTest extends FeatureTestCase
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

    public function setUp(): void
    {
        parent::setUp();

        $this->cookieJar = new CookieJar();
    }

    /**
     * @test
     */
    public function itShouldUpdateModuleVersion()
    {
        $this->setVersion('7.0.1');

        echo 'Initial Version : ' . $this->getVersion();
        echo "\n";
        echo 'Initial Version : ' . $this->configurationRepository->getLastUpgrade();

        $response = $this->loginIntoBackoffice();

        echo "\n----\n";
        echo 'Initial Version : ' . $this->getVersion();
        echo "\n";
        echo 'Initial Version : ' . $this->configurationRepository->getLastUpgrade();

        $json = $this->getResponseJson($response);
        $this->module->getLogger()->info(print_r($json, true));

        $this->assertResponseOk($response);

        $this->assertEquals(\Ps_accounts::VERSION, $this->getVersion());
    }

    /**
     * @test
     */
    public function itShouldNotUpdateModuleVersion()
    {
        $this->setVersion('7.0.9');

        $response = $this->loginIntoBackoffice();

        $json = $this->getResponseJson($response);
        $this->module->getLogger()->info(print_r($json, true));

        $this->assertResponseOk($response);

        $this->assertEquals('7.0.9', $this->getVersion());
    }

    /**
     * @test
     */
    public function itShouldUpdateModuleVersionOnlyOnce()
    {
        // TODO expect updateLastUpgrade called 0 times
        //$this->replaceDependency();
    }

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

        $res = $this->displayLoginPage($jar);
        $this->assertResponseOk($res);

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
        $this->module->getLogger()->info(print_r($json, true));

        return $this->displayBackofficePage($json['redirect'], $jar);
    }

    /**
     * @param CookieJar $jar
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function displayLoginPage(CookieJar $jar)
    {
        $response = $this->client->get('/admin-dev/index.php?controller=AdminLogin', [
            'cookies' => $jar,
        ]);
        return $response;
    }

    /**
     * @param CookieJar $jar
     * @param array $form
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function postLogionForm(array $form, CookieJar $jar)
    {
        $response = $this->client->post('/admin-dev/index.php?rand=' . time(), [
            'form_params' => $form,
            'cookies' => $jar,
        ]);
        return $response;
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
}
