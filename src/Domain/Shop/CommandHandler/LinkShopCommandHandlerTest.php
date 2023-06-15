<?php

namespace PrestaShop\Module\PsAccounts\Domain\Shop\CommandHandler;

use PHPUnit\Framework\MockObject\MockObject;
use PrestaShop\Module\PsAccounts\Domain\Shop\Command\LinkShopCommand;
use PrestaShop\Module\PsAccounts\Domain\Shop\Dto\LinkShop;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\OwnerSession;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\ShopSession;
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
     * @var LinkShopCommandHandler
     */
    private $linkShopHandler;

    public function setUp(): void
    {
        parent::setUp();

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
        $request = new LinkShop([
            'shopId' => 1,
            'employeeId' => 1,
            'userToken' => 'foo',
            'shopToken' => 'bar',
            'userRefreshToken' => 'fooRefresh',
            'shopRefreshToken' => 'barRefresh',
        ]);

        $command = new LinkShopCommand($request);

        /* @phpstan-ignore-next-line  */
        $this->shopSession->expects($this->once())
            ->method('setToken')
            ->with(
                $request->shopToken,
                $request->shopRefreshToken
            );

        /* @phpstan-ignore-next-line  */
        $this->ownerSession->expects($this->once())
            ->method('setToken')
            ->with(
                $request->userToken,
                $request->userRefreshToken
            );

        /* @phpstan-ignore-next-line  */
        $this->ownerSession->expects($this->once())
            ->method('setEmployeeId')
            ->with($request->employeeId);

        // $commandBus = new CommandBus();
        // $commandBus->execute($command);
        $this->linkShopHandler->handle($command);

        // FIXME: expect hook to be triggered
    }
}
