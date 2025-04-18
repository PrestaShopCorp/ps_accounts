<?php

namespace PrestaShop\Module\PsAccounts\Tests\Feature\Api\v1\ShopLinkAccount;

use PrestaShop\Module\PsAccounts\Account\Session\Firebase\OwnerSession;
use PrestaShop\Module\PsAccounts\Account\Session\Firebase\ShopSession;
use PrestaShop\Module\PsAccounts\Http\Controller\AbstractRestController;
use PrestaShop\Module\PsAccounts\Tests\Feature\TestCase;

class StoreTest extends TestCase
{
    /**
     * @inject
     *
     * @var ShopSession
     */
    protected $shopSession;

    /**
     * @inject
     *
     * @var OwnerSession
     */
    protected $ownerSession;

    /**
     * @inject
     *
     * @var \PrestaShop\Module\PsAccounts\Account\Session\ShopSession
     */
    protected $session;

    public function set_up()
    {
        parent::set_up();

        $this->markTestSkipped();
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldSucceed()
    {
        $payload = [
            'shop_id' => 1,
            'uid' => $this->faker->uuid,
            'employee_id' => $this->faker->numberBetween(1),
            'owner_uid' => $this->faker->uuid,
            'owner_email' => $this->faker->safeEmail,
        ];

        $response = $this->client->post('/module/ps_accounts/apiV1ShopLinkAccount', [
            'headers' => [
                AbstractRestController::TOKEN_HEADER => (string) $this->encodePayload($payload)
            ],
        ]);

        $json = $this->getResponseJson($response);

        $this->module->getLogger()->info(print_r($json, true));

        $this->assertResponseOk($response);
        $this->assertBodySubsetOrMarkAsIncomplete(['success' => true], $json);

        \Configuration::clearConfigurationCacheForTesting();
        \Configuration::loadConfiguration();

        $this->assertTrue($this->shopStatus->exists());
        $this->assertEquals($payload['uid'], $this->shopStatus->getShopUuid());
        $this->assertEquals($payload['employee_id'], $this->shopStatus->getEmployeeId());
        $this->assertEquals($payload['owner_uid'], $this->shopStatus->getOwnerUuid());
        $this->assertEquals($payload['owner_email'], $this->shopStatus->getOwnerEmail());
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldSucceedWithoutEmployeeId()
    {
        $shopUuid = $this->faker->uuid;
        $userUuid = $this->faker->uuid;
        $email = $this->faker->safeEmail;

        $payload = [
            'shop_id' => 1,
            'uid' => $shopUuid,
            //'employee_id' => $employeeId,
        ];

        $response = $this->client->post('/module/ps_accounts/apiV1ShopLinkAccount', [
            'headers' => [
                AbstractRestController::TOKEN_HEADER => (string) $this->encodePayload($payload)
            ],
        ]);

        $json = $this->getResponseJson($response);

        $this->module->getLogger()->info(print_r($json, true));

        $this->assertResponseOk($response);
        $this->assertBodySubsetOrMarkAsIncomplete(['success' => true], $json);

        \Configuration::clearConfigurationCacheForTesting();
        \Configuration::loadConfiguration();

        $this->assertTrue($this->shopStatus->exists());
        $this->assertEquals($shopUuid, $this->shopStatus->getShopUuid());
        $this->assertEquals(null, $this->shopStatus->getEmployeeId());
    }

}
