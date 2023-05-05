<?php

namespace PrestaShop\Module\PsAccounts\Domain\Shop\CommandHandler;

use PHPUnit\Framework\MockObject\MockObject;
use PrestaShop\Module\PsAccounts\Domain\Shop\Command\LinkShopCommand;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\OwnerSession;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\ShopSession;
use PrestaShop\Module\PsAccounts\Domain\Shop\Exception\RefreshTokenException;
use PrestaShop\Module\PsAccounts\Dto\Api\UpdateShopLinkAccountRequest;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class LinkShopCommandHandlerTest extends TestCase
{
    /**
     * @var MockObject|ShopSession|(ShopSession&MockObject)
     */
    private $shopSession;

    /**
     * @var MockObject|OwnerSession|(OwnerSession&MockObject)
     */
    private $ownerSession;

    /**
     * @var ConfigurationRepository
     */
    private $mockedConfigurationRepository;

    /**
     * @var LinkShopCommandHandler
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
        $this->shopSession = $this->createMock(ShopSession::class);
        $this->ownerSession = $this->createMock(OwnerSession::class);

        $this->linkShopHandler = new LinkShopCommandHandler(
            $this->shopSession,
            $this->ownerSession
        );
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldHandleLinkShop(): void
    {
        $request = new UpdateShopLinkAccountRequest([
            'shop_id' => 1,
            'employee_id' => 1,
            'user_token' => 'foo',
            'shop_token' => 'bar',
            'user_refresh_token' => 'fooRefresh',
            'shop_refresh_token' => 'barRefresh',
        ]);

        $command = new LinkShopCommand($request, false);

        /* @phpstan-ignore-next-line  */
        $this->shopSession->expects($this->once())
            ->method('setToken')
            ->with(
                $request->shop_token,
                $request->shop_refresh_token
            );

        /* @phpstan-ignore-next-line  */
        $this->ownerSession->expects($this->once())
            ->method('setToken')
            ->with(
                $request->user_token,
                $request->user_refresh_token
            );

        /* @phpstan-ignore-next-line  */
        $this->ownerSession->expects($this->once())
            ->method('setEmployeeId')
            ->with($request->employee_id);

        /* @phpstan-ignore-next-line  */
        $this->mockedConfigurationRepository->expects($this->once())
            ->method('updateLoginEnabled')
            ->with(true);

        // $commandBus = new CommandBus();
        // $commandBus->execute($command);
        $this->linkShopHandler->handle($command);

        // FIXME: expect hook to be triggered
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldThrowRefreshTokenException(): void
    {
        $request = new UpdateShopLinkAccountRequest([
            'shop_id' => 1,
            'employee_id' => 1,
            'user_token' => 'foo',
            'shop_token' => 'bar',
            'user_refresh_token' => 'fooRefresh',
            'shop_refresh_token' => 'barRefresh',
        ]);

        $command = new LinkShopCommand($request, true);

        // FIXME: this is just an example, not a meaningful test here
        /* @phpstan-ignore-next-line  */
        $this->shopSession->method('verifyToken')
            ->willThrowException(new RefreshTokenException());

        $this->expectException(RefreshTokenException::class);

        $this->linkShopHandler->handle($command);
    }
}
