<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Account\QueryHandler;

use PHPUnit\Framework\MockObject\MockObject;
use PrestaShop\Module\PsAccounts\Account\Query\GetContextQuery;
use PrestaShop\Module\PsAccounts\Account\QueryHandler\GetContextHandler;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Service\UpgradeService;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class GetContextHandlerTest extends TestCase
{
    /**
     * @var ShopProvider&MockObject
     */
    protected $shopProvider;

    /**
     * @inject
     *
     * @var UpgradeService
     */
    protected $upgradeService;

    /**
     * @test
     */
    public function itShouldReturnsExpectedContext()
    {
        $expectedShops = [
            ['id_shop' => 1, 'name' => 'Shop 1'],
            ['id_shop' => 2, 'name' => 'Shop 2'],
        ];

        $this->shopProvider = $this->createMock(ShopProvider::class);
        $this->shopProvider->method('getShops')->willReturn($expectedShops);

        $result = $this->getHandler()->handle(new GetContextQuery());

        $this->assertArrayHasKey('ps_accounts', $result);
        $this->assertArrayHasKey('groups', $result);

        $this->assertSame([
            'last_succeeded_upgrade_version' => '8.0.0',
            'module_version_from_files' => '8.0.0',
        ], $result['ps_accounts']);

        $this->assertSame($expectedShops, $result['groups']);
    }

    /**
     * @test
     */
    public function itShouldThrowsUnknownStatusException()
    {
        $this->shopProvider = $this->createMock(ShopProvider::class);
        $this->shopProvider->method('getShops')->will($this->throwException(
            new \PrestaShop\Module\PsAccounts\Account\Exception\UnknownStatusException('Unknown status')
        ));

        $this->expectException(\PrestaShop\Module\PsAccounts\Account\Exception\UnknownStatusException::class);

        $this->getHandler()->handle(new GetContextQuery());
    }


    /**
     * @return GetContextHandler
     */
    private function getHandler()
    {
        return new GetContextHandler(
            $this->shopProvider,
            $this->upgradeService
        );
    }
}
