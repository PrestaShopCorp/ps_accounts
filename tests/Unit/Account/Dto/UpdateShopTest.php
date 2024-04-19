<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Account\Dto;

use PrestaShop\Module\PsAccounts\Account\Dto;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class UpdateShopTest extends TestCase
{
    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldFailOnMissingMandatoryProperty()
    {
        $this->expectException(\Exception::class);

        new Dto\UpdateShop([
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
     * @throws \Exception
     */
    public function itShouldFailOnUnexpectedProperty()
    {
        $this->expectException(\Exception::class);

        new Dto\UpdateShop([
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
