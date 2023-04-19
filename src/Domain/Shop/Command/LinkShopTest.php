<?php

namespace PrestaShop\Module\PsAccounts\Domain\Shop\Command;

use PrestaShop\Module\PsAccounts\Dto\Api\UpdateShopLinkAccountRequest;
use PrestaShop\Module\PsAccounts\Exception\RefreshTokenException;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Repository\Support\ShopTokenRepository;
use PrestaShop\Module\PsAccounts\Repository\Support\UserTokenRepository;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class LinkShopTest extends TestCase
{
    /**
     * @var ShopTokenRepository
     */
    private $shopTokenRepository;

    /**
     * @var UserTokenRepository
     */
    private $userTokenRepository;

    /**
     * @var ConfigurationRepository
     */
    private $mockedConfigurationRepository;

    /**
     * @var LinkShopHandler
     */
    private $linkShopHandler;

    public function setUp(): void
    {
        parent::setUp();

//        $this->mockedConfigurationRepository = $this->getMockBuilder(ConfigurationRepository::class)
//            ->setConstructorArgs([$this->module->getService(Configuration::class)])
//            ->onlyMethods(['updateEmployeeId', 'updateLoginEnabled'])
//            ->getMock();

        $this->mockedConfigurationRepository = $this->createMock(ConfigurationRepository::class);
        $this->shopTokenRepository = $this->createMock(ShopTokenRepository::class);
        $this->userTokenRepository = $this->createMock(UserTokenRepository::class);

        $this->linkShopHandler = new LinkShopHandler(
            $this->shopTokenRepository,
            $this->userTokenRepository,
            $this->mockedConfigurationRepository
        );
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldHandleLinkShop()
    {
        $request = new UpdateShopLinkAccountRequest([
            'shop_id' => 1,
            'employee_id' => 1,
            'user_token' => 'foo',
            'shop_token' => 'bar',
            'user_refresh_token' => 'fooRefresh',
            'shop_refresh_token' => 'barRefresh',
        ]);

        $command = new LinkShop(
            new UpdateShopLinkAccountRequest($request),
            false
        );

        $this->shopTokenRepository->expects($this->once())
            ->method('updateCredentials')
            ->with(
                $request->shop_token,
                $request->shop_refresh_token
            );

        $this->userTokenRepository->expects($this->once())
            ->method('updateCredentials')
            ->with(
                $request->user_token,
                $request->user_refresh_token
            );

        $this->mockedConfigurationRepository->expects($this->once())
            ->method('updateEmployeeId')
            ->with($request->employee_id);

        $this->mockedConfigurationRepository->expects($this->once())
            ->method('updateLoginEnabled')
            ->with(true);

        // $commandBus = new CommandBus();
        // $commandBus->execute($command);
        $this->linkShopHandler->handle($command);
    }

    /**
     * @test
     */
    public function itShouldThrowRefreshTokenException()
    {
        $request = new UpdateShopLinkAccountRequest([
            'shop_id' => 1,
            'employee_id' => 1,
            'user_token' => 'foo',
            'shop_token' => 'bar',
            'user_refresh_token' => 'fooRefresh',
            'shop_refresh_token' => 'barRefresh',
        ]);

        $command = new LinkShop(
            new UpdateShopLinkAccountRequest($request),
            true
        );

        // FIXME: this is just an example, not a meaningfully test
        $this->shopTokenRepository->method('verifyToken')
            ->willThrowException(new RefreshTokenException());

        $this->expectException(RefreshTokenException::class);

        $this->linkShopHandler->handle($command);
    }
}
