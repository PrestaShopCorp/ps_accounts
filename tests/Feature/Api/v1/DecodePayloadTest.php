<?php

namespace PrestaShop\Module\PsAccounts\Tests\Feature\Api\v1;

use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key;
use PrestaShop\Module\PsAccounts\Controller\AbstractRestController;
use PrestaShop\Module\PsAccounts\Provider\RsaKeysProvider;
use PrestaShop\Module\PsAccounts\Tests\Feature\FeatureTestCase;

class DecodePayloadTest extends FeatureTestCase
{
    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldEncodePayload()
    {
        $payload = [
            "refreshToken" => 'AOvuKvRgjx-ajJn9TU0yIAe7qQc5rEBmbnTfndKifCOV9XWKokdaCs1s_IQ1WxbwKfJ_eYhviCLBAYMqCXlVVNUYv3WHygzORqY-h8Pgt52CEq_u4QThl2nmB4a7wD_dgzv_GRmNIDgxkEC-IZMW3jG7xH0HHbPLXDDVAMHuDtupqos_07uXW60',
            "jwt" => 'eyJhbGciOiJSUzI1NiIsImtpZCI6IjRlMDBlOGZlNWYyYzg4Y2YwYzcwNDRmMzA3ZjdlNzM5Nzg4ZTRmMWUiLCJ0eXAiOiJKV1QifQ.eyJpc3MiOiJodHRwczovL3NlY3VyZXRva2VuLmdvb2dsZS5jb20vc2hvcHRlc3QtYzMyMzIiLCJhdWQiOiJzaG9wdGVzdC1jMzIzMiIsImF1dGhfdGltZSI6MTYxNjA2MzkzNywidXNlcl9pZCI6Ik1Ucm9MUVduaExlYUtxMGJzajNMdkFpWlRvOTIiLCJzdWIiOiJNVHJvTFFXbmhMZWFLcTBic2ozTHZBaVpUbzkyIiwiaWF0IjoxNjE2MDYzOTM3LCJleHAiOjE2MTYwNjc1MzcsImVtYWlsIjoiaHR0cHNsaW04MDgwMTYxNTgwMzUxNTVAc2hvcC5wcmVzdGFzaG9wLmNvbSIsImVtYWlsX3ZlcmlmaWVkIjp0cnVlLCJmaXJlYmFzZSI6eyJpZGVudGl0aWVzIjp7ImVtYWlsIjpbImh0dHBzbGltODA4MDE2MTU4MDM1MTU1QHNob3AucHJlc3Rhc2hvcC5jb20iXX0sInNpZ25faW5fcHJvdmlkZXIiOiJjdXN0b20ifX0.J6rX8H6roDa4Fq62vhlXtm702V9YnhqT2JLts31Jy2wvn9h5Qf-FxHInrGlQyHWqtPcM_mxFlgcTNYfZNNyuzF_5Iz-v6rKtCXK7tmtaw6qKSM3sDQAvGpPBRVuhxVxUUqgXkT6DeznfFTYOoD96P912jFF6KroObLtJfDJsfhvncaSqh3pcMbKUP6gwe05Xyg6g_psY48OpYjia6X9b0Hn1orgdOH15JE4SWM5nk0XXcbs98qlNKNu2SbmgrQqu9zv-3aiC44LWAp_7UTDLQvXTTpVk4GbmXNCoD26bOwPm75tm7b2X4yq5d4WAvkBQmTI5-ug1Kf7ZZyPtl1X7kw',
        ];

        /** @var RsaKeysProvider $shopKeysService */
        $shopKeysService = $this->module->getService(RsaKeysProvider::class);

        $jwt = (new Parser())->parse((string) $this->encodePayload($payload));

        $this->assertTrue($jwt->verify(new Sha256(), new Key($shopKeysService->getPublicKey())));

        $this->assertArraySubset($jwt->claims()->all(), $payload);
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldDecodeMethod()
    {
        $response = $this->client->get('/module/ps_accounts/apiV1ShopUrl', [
            'headers' => [
                AbstractRestController::TOKEN_HEADER => $this->encodePayload([
                    'shop_id' => 1,
                ])
            ],
        ]);

        $json = $response->json();

        $this->module->getLogger()->info(print_r($json, true));

        $this->assertResponseOk($response);
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldDecodeMethodFromPayload()
    {
        $response = $this->client->post('/module/ps_accounts/apiV1ShopUrl', [
            'headers' => [
                AbstractRestController::TOKEN_HEADER => $this->encodePayload([
                    'method' => 'GET',
                    'shop_id' => 1,
                ])
            ],
        ]);

        $json = $response->json();

        $this->module->getLogger()->info(print_r($json, true));

        $this->assertResponseOk($response);
    }
}
