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
        $this->markTestSkipped('This verification step has been deprecated');

        $payload = [
            'shop_id' => 1,
            'hmac' => base64_encode((string) (new Hmac\Sha256())->createHash('foo', new Key('bar'))),
        ];

        $response = $this->client->post('/module/ps_accounts/apiV1ShopHmac', [
            'headers' => [
                AbstractRestController::TOKEN_HEADER => $this->encodePayload($payload),
            ],
        ]);

        $json = $response->json();

        $this->module->getLogger()->info(print_r($json, true));

        $this->assertResponseOk($response);

        $this->assertArraySubset(['success' => true], $json);

        $response = $this->client->get('/upload/' . $payload['shop_id'] . '.txt');

        $hmac = $response->getBody()->getContents();

        $this->module->getLogger()->info(print_r($hmac, true));

        $this->assertResponseOk($response);

        $this->assertEquals($payload['hmac'], trim($hmac));
    }
}
