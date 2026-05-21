<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Context\ShopContext;

use PrestaShop\Module\PsAccounts\Context\ShopContext;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class ExecInShopContextTest extends TestCase
{
    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldSetAndRestoreShopContext()
    {
        /** @var ShopContext $shopContext */
        $shopContext = $this->module->getService(ShopContext::class);

        $origShopId = $this->configuration->getIdShop();
        $localShopId = $this->faker->numberBetween($origShopId + 1);

        $shopContext->execInShopContext($localShopId, function () use ($localShopId) {
            $this->assertEquals($localShopId, $this->configuration->getIdShop());
        });

        $this->assertEquals($origShopId, $this->configuration->getIdShop());
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldRestoreShopContextOnException()
    {
        /** @var ShopContext $shopContext */
        $shopContext = $this->module->getService(ShopContext::class);

        $origShopId = $this->configuration->getIdShop();
        $localShopId = $this->faker->numberBetween($origShopId + 1);

        try {
            $shopContext->execInShopContext($localShopId, function () use ($localShopId) {
                $this->assertEquals($localShopId, $this->configuration->getIdShop());
                throw new \Exception('closure failed');
            });
        } catch (\Exception $e) {
        }

        $this->assertEquals($origShopId, $this->configuration->getIdShop());
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldSetAndRestoreShopGroupContext()
    {
        /** @var ShopContext $shopContext */
        $shopContext = $this->module->getService(ShopContext::class);

        $origShopGroupId = $this->configuration->getIdShopGroup();
        $shopId = (int) \Shop::getContextShopID(true) ?: 1;
        $expectedGroupId = (int) (new \Shop($shopId))->id_shop_group;

        $shopContext->execInShopContext($shopId, function () use ($expectedGroupId) {
            $this->assertEquals($expectedGroupId, $this->configuration->getIdShopGroup());
        });

        $this->assertEquals($origShopGroupId, $this->configuration->getIdShopGroup());
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldRestoreShopGroupContextOnException()
    {
        /** @var ShopContext $shopContext */
        $shopContext = $this->module->getService(ShopContext::class);

        $origShopGroupId = $this->configuration->getIdShopGroup();
        $shopId = (int) \Shop::getContextShopID(true) ?: 1;

        try {
            $shopContext->execInShopContext($shopId, function () {
                throw new \Exception('closure failed');
            });
        } catch (\Exception $e) {
        }

        $this->assertEquals($origShopGroupId, $this->configuration->getIdShopGroup());
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldResolveShopGroupIdFromPsShopNotFromCurrentContext()
    {
        /** @var ShopContext $shopContext */
        $shopContext = $this->module->getService(ShopContext::class);

        // simulate a CONTEXT_ALL caller: id_shop_group unset on the adapter
        $this->configuration->setIdShopGroup(null);

        $shopId = (int) \Shop::getContextShopID(true) ?: 1;
        $expectedGroupId = (int) (new \Shop($shopId))->id_shop_group;

        $shopContext->execInShopContext($shopId, function () use ($expectedGroupId) {
            $this->assertEquals($expectedGroupId, $this->configuration->getIdShopGroup());
        });
    }
}
