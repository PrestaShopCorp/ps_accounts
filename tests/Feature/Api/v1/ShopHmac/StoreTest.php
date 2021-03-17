<?php

namespace PrestaShop\Module\PsAccounts\Tests\Feature\Api\v1\ShopHmac;

use Lcobucci\JWT\Signer\Hmac;
use Lcobucci\JWT\Signer\Key;
use PrestaShop\Module\PsAccounts\Controller\AbstractRestController;
use PrestaShop\Module\PsAccounts\Tests\Feature\FeatureTestCase;

class StoreTest extends FeatureTestCase
{
    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldSucceed()
    {
        $payload = [
            'shop_id' => 1,
            'hmac' => base64_encode((string) (new Hmac\Sha256())->createHash('foo', new Key('bar'))),
        ];

        $response = $this->client->post('/module/ps_accounts/apiV1ShopHmac', [
            'query' => [
                AbstractRestController::PAYLOAD_PARAM => $this->encodePayload($payload),
            ],
        ]);

        $json = $response->json();

        print_r($json);

        $this->assertResponseOk($response);

        $this->assertArraySubset(['success' => true], $json);

        // TODO : read file with guzzle
        $response = $this->client->post('/upload/' . $payload['shop_id'] . '.txt');

        $hmac = $response->getBody()->getContents();

        print_r($hmac);

        $this->assertResponseOk($response);

        $this->assertEquals($payload['hmac'], trim($hmac));
    }
}
