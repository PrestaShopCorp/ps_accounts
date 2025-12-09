<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Account;

use PrestaShop\Module\PsAccounts\Account\ShopUrl;
use PrestaShop\Module\PsAccounts\Service\Accounts\Resource\ShopStatus;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class ShopUrlTest extends TestCase
{
    /**
     * @test
     */
    public function itShouldReturnTrueWhenFrontendUrlChanged()
    {
        $status = new ShopStatus([
            'frontendUrl' => 'https://example.com',
            'backOfficeUrl' => 'https://admin.example.com',
        ]);

        $localShopUrl = new ShopUrl(
            'https://admin.example.com',
            'https://different-example.com',
            1
        );
        $cloudShopUrl = ShopUrl::createFromStatus($status, 1);
        $this->assertTrue($cloudShopUrl->frontendUrlNotEquals($localShopUrl));
    }

    /**
     * @test
     */
    public function itShouldReturnFalseWhenFrontendUrlNotChanged()
    {
        $status = new ShopStatus([
            'frontendUrl' => 'https://example.com',
            'backOfficeUrl' => 'https://admin.example.com',
        ]);

        $localShopUrl = new ShopUrl(
            'https://admin.example.com',
            'https://example.com',
            1
        );

        $cloudShopUrl = ShopUrl::createFromStatus($status, 1);
        $this->assertFalse($cloudShopUrl->frontendUrlNotEquals($localShopUrl));
    }

    /**
     * @test
     */
    public function itShouldReturnFalseWhenFrontendUrlNotChangedWithTrailingSlash()
    {
        $status = new ShopStatus([
            'frontendUrl' => 'https://example.com/',
            'backOfficeUrl' => 'https://admin.example.com',
        ]);

        $localShopUrl = new ShopUrl(
            'https://admin.example.com',
            'https://example.com',
            1
        );

        $cloudShopUrl = ShopUrl::createFromStatus($status, 1);
        $this->assertFalse($cloudShopUrl->frontendUrlNotEquals($localShopUrl));
    }

    /**
     * @test
     */
    public function itShouldReturnTrueWhenBackOfficeUrlChangedAndFrontendUrlNotChanged()
    {
        $status = new ShopStatus([
            'frontendUrl' => 'https://example.com',
            'backOfficeUrl' => 'https://admin.example.com',
        ]);

        $cloudShopUrl = ShopUrl::createFromStatus($status, 1);
        $localShopUrl = new ShopUrl(
            'https://different-admin.example.com',
            'https://example.com',
            1
        );

        $this->assertTrue($cloudShopUrl->backOfficeUrlNotEquals($localShopUrl));
    }

    /**
     * @test
     */
    public function itShouldReturnTrueWhenBackOfficeUrlChangedButFrontendUrlAlsoChanged()
    {
        $status = new ShopStatus([
            'frontendUrl' => 'https://example.com',
            'backOfficeUrl' => 'https://admin.example.com',
        ]);

        $cloudShopUrl = ShopUrl::createFromStatus($status, 1);
        $localShopUrl = new ShopUrl(
            'https://different-admin.example.com',
            'https://different-example.com',
            1
        );

        // backOfficeUrlNotEquals only checks if BO URL changed, not if frontend also changed
        $this->assertTrue($cloudShopUrl->backOfficeUrlNotEquals($localShopUrl));
    }

    /**
     * @test
     */
    public function itShouldReturnFalseWhenBackOfficeUrlNotChanged()
    {
        $status = new ShopStatus([
            'frontendUrl' => 'https://example.com',
            'backOfficeUrl' => 'https://admin.example.com',
        ]);

        $cloudShopUrl = ShopUrl::createFromStatus($status, 1);
        $localShopUrl = new ShopUrl(
            'https://admin.example.com',
            'https://example.com',
            1
        );

        $this->assertFalse($cloudShopUrl->backOfficeUrlNotEquals($localShopUrl));
    }

    /**
     * @test
     */
    public function itShouldReturnFalseWhenBackOfficeUrlNotChangedWithTrailingSlash()
    {
        $status = new ShopStatus([
            'frontendUrl' => 'https://example.com',
            'backOfficeUrl' => 'https://admin.example.com/',
        ]);

        $cloudShopUrl = ShopUrl::createFromStatus($status, 1);
        $localShopUrl = new ShopUrl(
            'https://admin.example.com',
            'https://example.com',
            1
        );

        $this->assertFalse($cloudShopUrl->backOfficeUrlNotEquals($localShopUrl));
    }

    /**
     * @test
     */
    public function itShouldReturnTrueWhenBackOfficeUrlChangedAndFrontendUrlNotChangedWithTrailingSlash()
    {
        $status = new ShopStatus([
            'frontendUrl' => 'https://example.com/',
            'backOfficeUrl' => 'https://admin.example.com',
        ]);

        $cloudShopUrl = ShopUrl::createFromStatus($status, 1);
        $localShopUrl = new ShopUrl(
            'https://different-admin.example.com',
            'https://example.com',
            1
        );

        $this->assertTrue($cloudShopUrl->backOfficeUrlNotEquals($localShopUrl));
    }

    /**
     * @test
     */
    public function itShouldReturnFalseWhenBothUrlsNotChanged()
    {
        $status = new ShopStatus([
            'frontendUrl' => 'https://example.com',
            'backOfficeUrl' => 'https://admin.example.com',
        ]);

        $cloudShopUrl = ShopUrl::createFromStatus($status, 1);
        $localShopUrl = new ShopUrl(
            'https://admin.example.com',
            'https://example.com',
            1
        );

        $this->assertFalse($cloudShopUrl->frontendUrlNotEquals($localShopUrl));
        $this->assertFalse($cloudShopUrl->backOfficeUrlNotEquals($localShopUrl));
    }
}

