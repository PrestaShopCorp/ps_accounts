<?php

namespace PrestaShop\Module\PsAccounts\Tests\Feature\Api\v1\ShopLinkAccount;

use PrestaShop\Module\PsAccounts\Account\ShopIdentity;
use PrestaShop\Module\PsAccounts\Account\Session\Firebase\OwnerSession;
use PrestaShop\Module\PsAccounts\Account\Session\Firebase\ShopSession;
use PrestaShop\Module\PsAccounts\Account\Token\NullToken;
use PrestaShop\Module\PsAccounts\Api\Controller\AbstractRestController;
use PrestaShop\Module\PsAccounts\Adapter\ConfigurationKeys;
use PrestaShop\Module\PsAccounts\Tests\Feature\FeatureTestCase;

class DeleteTest extends FeatureTestCase
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

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldSucceed()
    {
        $this->shopIdentity->update(new \PrestaShop\Module\PsAccounts\Account\Dto\LinkShop([
            'shopId' => $this->faker->numberBetween(),
            'uid' => $this->faker->uuid,
            'employeeId' => $this->faker->numberBetween()
        ]));

        $this->shopSession->setToken((string) $this->makeJwtToken(new \DateTimeImmutable(), ['foo' => 'bar']));
        $this->ownerSession->setToken((string) $this->makeJwtToken(new \DateTimeImmutable(), ['foo' => 'bar']));
        $this->session->setToken((string) $this->makeJwtToken(new \DateTimeImmutable(), ['foo' => 'bar']));

        $response = $this->client->delete('/module/ps_accounts/apiV1ShopLinkAccount', [
            'headers' => [
                AbstractRestController::TOKEN_HEADER => (string) $this->encodePayload([
                    'method' => 'DELETE',
                    'shop_id' => 1,
                ])
            ],
        ]);

        $json = $this->getResponseJson($response);

        $this->module->getLogger()->info(print_r($json, true));

        $this->assertResponseDeleted($response);

        // FIXME: empty response
        // $this->assertArraySubset(['success' => true], $json);

        \Configuration::clearConfigurationCacheForTesting();
        \Configuration::loadConfiguration();

        $this->assertFalse($this->shopIdentity->exists());

        $this->assertEmpty($this->shopIdentity->getShopUuid());
        $this->assertEmpty($this->shopIdentity->getEmployeeId());
        $this->assertEmpty($this->shopIdentity->getOwnerUuid());
        $this->assertEmpty($this->shopIdentity->getOwnerEmail());

        $this->assertInstanceOf(NullToken::class, $this->shopSession->getToken()->getJwt());
        $this->assertInstanceOf(NullToken::class, $this->ownerSession->getToken()->getJwt());
        $this->assertInstanceOf(NullToken::class, $this->session->getToken()->getJwt());

        // compat
        $this->assertEmpty($this->configuration->get(ConfigurationKeys::PS_ACCOUNTS_FIREBASE_EMAIL));
        $this->assertEmpty($this->configuration->get(ConfigurationKeys::PSX_UUID_V4));
    }
}
