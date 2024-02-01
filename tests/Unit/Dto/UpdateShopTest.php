<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Dto;

use PrestaShop\Module\PsAccounts\Account\Dto\UpdateShop;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class UpdateShopTest extends TestCase
{
    /**
     * @test
     */
    public function itShouldFailOnMissingMandatoryProperty()
    {
        $this->expectException(\Exception::class);

        new UpdateShop([
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

        new \PrestaShop\Module\PsAccounts\Account\Dto\UpdateShop([
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
