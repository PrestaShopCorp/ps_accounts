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
}
