<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Account\CommandHandler;

use Error;
use PrestaShop\Module\PsAccounts\Identity\Domain\Identity;
use PrestaShop\Module\PsAccounts\Identity\Domain\OAuth2Client;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class IdentityTest extends TestCase
{
    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldCreateAnIdentity()
    {
        $shopId = $this->faker->uuid;
        $cloudShopId = $this->faker->uuid;
        $oauth2Client = new OAuth2Client($this->faker->uuid(), $this->faker->password());

        $identity = new Identity($shopId);
        $identity->create($cloudShopId, $oauth2Client);

        $this->assertEquals($shopId, $identity->shopId());
        $this->assertEquals($cloudShopId, $identity->cloudShopId());
        $this->assertEquals($oauth2Client, $identity->oauth2Client());
    }


    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldCannotCreateAnIdentityIfIdentityAlreadyExists()
    {
        $shopId = $this->faker->uuid;
        $cloudShopId = $this->faker->uuid;
        $oauth2Client = new OAuth2Client($this->faker->uuid(), $this->faker->password());

        $identity = new Identity($shopId, $cloudShopId, $oauth2Client);


        $this->expectException(Error::class);
        $this->expectExceptionMessage('The store already have an identity');

        $identity->create($cloudShopId, $oauth2Client);
    }
}
