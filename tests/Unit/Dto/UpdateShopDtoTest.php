<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Dto;

use PrestaShop\Module\PsAccounts\Dto\UpdateShop;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class UpdateShopDtoTest extends TestCase
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

        new UpdateShop([
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

    /**
     * @test
     */
    public function itShouldEnforceDomainWithScheme()
    {
        $dto = new UpdateShop([
            'shopId' => 4,
            'name' => $this->faker->slug,
            'virtualUri' => $this->faker->domainWord,
            'physicalUri' => $this->faker->domainWord,
            'domain' => $this->faker->domainName,
            'sslDomain' => $this->faker->domainName,
            'boBaseUrl' => $this->faker->url
        ]);

        $this->assertStringStartsWith('https://', $dto->sslDomain);
        $this->assertStringStartsWith('http://', $dto->domain);
    }
}
