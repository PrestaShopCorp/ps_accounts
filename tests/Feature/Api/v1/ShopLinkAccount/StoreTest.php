<?php

namespace PrestaShop\Module\PsAccounts\Tests\Feature\Api\v1\ShopLinkAccount;

use PrestaShop\Module\PsAccounts\Account\LinkShop;
use PrestaShop\Module\PsAccounts\Account\Session\Firebase\OwnerSession;
use PrestaShop\Module\PsAccounts\Account\Session\Firebase\ShopSession;
use PrestaShop\Module\PsAccounts\Api\Controller\AbstractRestController;
use PrestaShop\Module\PsAccounts\Tests\Feature\FeatureTestCase;

class StoreTest extends FeatureTestCase
{
    /**
     * @inject
     * @var LinkShop
     */
    protected $linkShop;

    /**
     * @inject
     * @var ShopSession
     */
    protected $shopSession;

    /**
     * @inject
     * @var OwnerSession
     */
    protected $ownerSession;

    /**
     * @inject
     * @var \PrestaShop\Module\PsAccounts\Account\Session\ShopSession
     */
    protected $session;

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldSucceed()
    {
        $shopUuid = $this->faker->uuid;
        $userUuid = $this->faker->uuid;
        $email = $this->faker->safeEmail;
        $employeeId = $this->faker->numberBetween(1);

        $payload = [
            'shop_id' => 1,
            'uid' => $shopUuid,
            'employee_id' => $employeeId,
            'user_uid' => $this->faker->uuid,
            'email' => $this->faker->safeEmail,
        ];

        $response = $this->client->post('/module/ps_accounts/apiV1ShopLinkAccount', [
            'headers' => [
                AbstractRestController::TOKEN_HEADER => (string) $this->encodePayload($payload)
            ],
        ]);

        $json = $this->getResponseJson($response);

        $this->module->getLogger()->info(print_r($json, true));

        $this->assertResponseOk($response);
        $this->assertArraySubset(['success' => true], $json);

        \Configuration::clearConfigurationCacheForTesting();
        \Configuration::loadConfiguration();

        $this->assertEquals($shopUuid, $this->linkShop->getShopUuid());
        $this->assertEquals($employeeId, $this->linkShop->getEmployeeId());
        $this->assertEquals($userUuid, $this->linkShop->getOwnerUuid());
        $this->assertEquals($email, $this->linkShop->getOwnerEmail());
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
        $this->assertArraySubset(['success' => true], $json);

        \Configuration::clearConfigurationCacheForTesting();
        \Configuration::loadConfiguration();

        $this->assertEquals($shopUuid, $this->linkShop->getShopUuid());
        $this->assertEquals(null, $this->linkShop->getEmployeeId());
    }

}
