<?php

<<<<<<<< HEAD:tests/Unit/Account/Dto/UpdateShopTest.php
namespace PrestaShop\Module\PsAccounts\Tests\Unit\Account\Dto;

use PrestaShop\Module\PsAccounts\Account\Dto;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class UpdateShopTest extends TestCase
========
namespace PrestaShop\Module\PsAccounts\Tests\Unit\Dto;

use PrestaShop\Module\PsAccounts\Dto\UpdateShop;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class UpdateShopDtoTest extends TestCase
>>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2):tests/Unit/Dto/UpdateShopDtoTest.php
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
