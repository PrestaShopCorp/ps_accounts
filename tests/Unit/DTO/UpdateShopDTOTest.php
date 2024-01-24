<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\DTO;

use PrestaShop\Module\PsAccounts\Api\Client\UpdateShopDto;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class UpdateShopDTOTest extends TestCase
{
    /**
     * @test
     */
    public function itShouldFailOnMissingMandatoryProperty()
    {
        $this->expectException(\Exception::class);

        new UpdateShopDto([
            'shopId' => 4,
            //'name' => $this->faker->slug,
            'virtualUri' => $this->faker->domainWord,
            'physicalUri' => $this->faker->domainWord,
            'domain' => $this->faker->domainName,
            'sslDomain' => $this->faker->domainName,
            'boBaseUrl' => $this->faker->url,
        ]);
    }

    /**
     * @test
     */
    public function itShouldFailOnUnexpectedProperty()
    {
        $this->expectException(\Exception::class);

        new \PrestaShop\Module\PsAccounts\Api\Client\UpdateShopDto([
            'foo' => 'bar',
            'shopId' => 4,
            'name' => $this->faker->slug,
            'virtualUri' => $this->faker->domainWord,
            'physicalUri' => $this->faker->domainWord,
            'domain' => $this->faker->domainName,
            'sslDomain' => $this->faker->domainName,
            'boBaseUrl' => $this->faker->url
        ]);
    }
}
