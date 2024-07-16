<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Account\CommandHandler;

use PrestaShop\Module\PsAccounts\Account\Command\UnlinkShopCommand;
use PrestaShop\Module\PsAccounts\Account\CommandHandler\UnlinkShopHandler;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Service\AnalyticsService;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class UnlinkShopHandlerTest extends TestCase
{
    /**
     * @inject
     *
     * @var AnalyticsService
     */
    protected $analyticsService;

    /**
     * @inject
     *
     * @var ShopProvider
     */
    protected $shopProvider;

    /**
     * @inject
     *
     * @var UnlinkShopHandler
     */
    protected $unlinkShopHandler;

    /**
     * @test
     */
    function itShouldTriggerSegmentEventWithErrorMsg()
    {
        $shopId = $this->shopProvider->getShopContext()->getContext()->shop->id;
        $shop = $this->shopProvider->formatShopData((array) \Shop::getShop($shopId));
        $this->analyticsService = $this->createMock(AnalyticsService::class);
        $this->analyticsService
            ->expects($this->once())
            ->method('trackShopUnlinkedOnError')
            ->with(
                $this->linkShop->getOwnerUuid(),
                $this->linkShop->getOwnerEmail(),
                $this->linkShop->getShopUuid(),
                $shop->frontUrl,
                $shop->url,
                'ps_accounts',
                'Unlinking Shop On Error'
            );
        $this->getUnlinkShopHandler()->handle(
            new UnlinkShopCommand($shopId, 'Unlinking Shop On Error')
        );
    }

    /**
     * @return UnlinkShopHandler
     */
    private function getUnlinkShopHandler()
    {
        return new UnlinkShopHandler(
            $this->linkShop,
            $this->analyticsService,
            $this->shopProvider
        );
    }
}
