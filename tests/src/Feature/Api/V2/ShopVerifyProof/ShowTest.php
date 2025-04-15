<?php

namespace PrestaShop\Module\PsAccounts\Tests\Feature\Api\v2\ShopVerifyProof;

use PrestaShop\Module\PsAccounts\Account\ProofManager;
use PrestaShop\Module\PsAccounts\Adapter\ConfigurationKeys;
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
        $shop = $this->shopProvider->formatShopData((array) \Shop::getShop(1));

        $proof = $this->manageProof->generateProof();

        $response = $this->client->get('/module/ps_accounts/apiV2ShopVerifyProof', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->makeBearer([
                        'aud' => [
                            'ps_accounts/' . $shop->uuid,
                        ],
                        'scp' => [
                            'shop.verify_proof',
                        ]
                    ]),
            ],
            'query' => [
                'shop_id' => $shop->id,
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
        $shop = $this->shopProvider->formatShopData((array) \Shop::getShop(1));

        //$proof = $this->manageProof->generateProof();

        \Db::getInstance()->execute(
            "DELETE FROM " . _DB_PREFIX_. "configuration " .
            "WHERE name ='" . ConfigurationKeys::PS_ACCOUNTS_IDENTITY_VERIFICATION_PROOF. "'"
        );

        $response = $this->client->get('/module/ps_accounts/apiV2ShopVerifyProof', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->makeBearer([
                        'aud' => [
                            'ps_accounts/' . $shop->uuid,
                        ],
                        'scp' => [
                            'shop.verify_proof',
                        ]
                    ]),
            ],
            'query' => [
                'shop_id' => $shop->id,
            ]
        ]);

        $json = $this->getResponseJson($response);

        $this->assertResponseOk($response);

        $this->assertFalse($json['proof']);
    }
}
