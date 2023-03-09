<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Presenter;

use PrestaShop\Module\PsAccounts\Exception\SshKeysNotFoundException;
use PrestaShop\Module\PsAccounts\Installer\Installer;
use PrestaShop\Module\PsAccounts\Presenter\PsAccountsPresenter;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;
use PrestaShop\Module\PsAccounts\Service\ShopLinkAccountService;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class PsAccountsPresenterTest extends TestCase
{
    /**
     * @var PsAccountsPresenter
     */
    private $presenter;

    /**
     * @var Installer
     */
    private $installer;

    public function setUp()
    {
        parent::setUp();

        $this->installer = $this->createMock(Installer::class);
        $this->installer->method('isEnabled')
            ->willReturn(true);
        $this->installer->method('getEnableUrl')
            ->willReturn($this->faker->url);

        $this->presenter = new PsAccountsPresenter(
            $this->module->getService(PsAccountsService::class),
            $this->module->getService(ShopProvider::class),
            $this->module->getService(ShopLinkAccountService::class),
            $this->installer,
            $this->module->getService(ConfigurationRepository::class),
            $this->module
        );
    }

    /**
     * @test
     *
     * @throws SshKeysNotFoundException
     */
    public function itShouldPresent()
    {
        $data = $this->presenter->present();

        //$this->assertArrayHasKey('psxName', $data);
        $this->assertContains([
            'psxName' => $this->module->name,
            'psIs17' => false,
            'psAccountsVersion' => $this->module->version,

            'psAccountsIsInstalled' => true,
            'psAccountsInstallLink' => null,

            'psAccountsIsEnabled' => $this->installer->isEnabled((string) $this->module->name),
            'psAccountsEnableLink' => $this->installer->getEnableUrl((string) $this->module->name, 'FooModule'),

            'psAccountsIsUptodate' => true,
            'psAccountsUpdateLink' => null,
        ], $data);
    }
}
