<?php

namespace PrestaShop\Module\PsAccounts\Tests\Feature\Api\v2\ShopVerifyProof;

use PrestaShop\Module\PsAccounts\Account\ProofManager;
use PrestaShop\Module\PsAccounts\Adapter\ConfigurationKeys;
use PrestaShop\Module\PsAccounts\Http\Controller\AbstractV2RestController;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Tests\Feature\Api\V2\TestCase;

class ShowTest extends TestCase
{
    /**
     * @inject
     *
     * @var ShopProvider
     */
    protected $shopProvider;

    /**
     * @inject
     *
     * @var ProofManager
     */
    protected $manageProof;

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldShowExpectedProof()
    {
        $shopUuid = $this->configurationRepository->getShopUuid();

        $proof = $this->manageProof->generateProof();

        $response = $this->client->get('/module/ps_accounts/apiV2ShopProof', [
            'headers' => [
                AbstractV2RestController::HEADER_AUTHORIZATION => 'Bearer ' . $this->makeBearer([
                        'aud' => [
                            'ps_accounts/' . $shopUuid,
                        ],
                        'scp' => [
                            'shop.proof.read',
                        ]
                    ]),
            ],
            'query' => [
                'shop_id' => 1,
            ]
        ]);

        $json = $this->getResponseJson($response);

        $this->assertResponseOk($response);

        $this->assertEquals($proof, $json['proof']);
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldShowEmptyProof()
    {
        $shopUuid = $this->configurationRepository->getShopUuid();

        //$proof = $this->manageProof->generateProof();

        \Db::getInstance()->execute(
            "DELETE FROM " . _DB_PREFIX_. "configuration " .
            "WHERE name ='" . ConfigurationKeys::PS_ACCOUNTS_SHOP_PROOF. "'"
        );

        $response = $this->client->get('/module/ps_accounts/apiV2ShopProof', [
            'headers' => [
                AbstractV2RestController::HEADER_AUTHORIZATION => 'Bearer ' . $this->makeBearer([
                        'aud' => [
                            'ps_accounts/' . $shopUuid,
                        ],
                        'scp' => [
                            'shop.proof.read',
                        ]
                    ]),
            ],
            'query' => [
                'shop_id' => 1,
            ]
        ]);

        $json = $this->getResponseJson($response);

        $this->assertResponseOk($response);

        $this->assertFalse($json['proof']);
    }
}
