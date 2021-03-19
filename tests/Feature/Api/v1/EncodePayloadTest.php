<?php

namespace PrestaShop\Module\PsAccounts\Tests\Feature\Api\v1;

use PrestaShop\Module\PsAccounts\Service\ShopKeysService;
use PrestaShop\Module\PsAccounts\Tests\Feature\FeatureTestCase;

class EncodePayloadTest extends FeatureTestCase
{
    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldEncodePayload()
    {
        $this->markTestSkipped();

        $payload = [
            "shopTokens" => [
                "refreshToken" => 'AOvuKvRgjx-ajJn9TU0yIAe7qQc5rEBmbnTfndKifCOV9XWKokdaCs1s_IQ1WxbwKfJ_eYhviCLBAYMqCXlVVNUYv3WHygzORqY-h8Pgt52CEq_u4QThl2nmB4a7wD_dgzv_GRmNIDgxkEC-IZMW3jG7xH0HHbPLXDDVAMHuDtupqos_07uXW60',
                "jwt" => 'eyJhbGciOiJSUzI1NiIsImtpZCI6IjRlMDBlOGZlNWYyYzg4Y2YwYzcwNDRmMzA3ZjdlNzM5Nzg4ZTRmMWUiLCJ0eXAiOiJKV1QifQ.eyJpc3MiOiJodHRwczovL3NlY3VyZXRva2VuLmdvb2dsZS5jb20vc2hvcHRlc3QtYzMyMzIiLCJhdWQiOiJzaG9wdGVzdC1jMzIzMiIsImF1dGhfdGltZSI6MTYxNjA2MzkzNywidXNlcl9pZCI6Ik1Ucm9MUVduaExlYUtxMGJzajNMdkFpWlRvOTIiLCJzdWIiOiJNVHJvTFFXbmhMZWFLcTBic2ozTHZBaVpUbzkyIiwiaWF0IjoxNjE2MDYzOTM3LCJleHAiOjE2MTYwNjc1MzcsImVtYWlsIjoiaHR0cHNsaW04MDgwMTYxNTgwMzUxNTVAc2hvcC5wcmVzdGFzaG9wLmNvbSIsImVtYWlsX3ZlcmlmaWVkIjp0cnVlLCJmaXJlYmFzZSI6eyJpZGVudGl0aWVzIjp7ImVtYWlsIjpbImh0dHBzbGltODA4MDE2MTU4MDM1MTU1QHNob3AucHJlc3Rhc2hvcC5jb20iXX0sInNpZ25faW5fcHJvdmlkZXIiOiJjdXN0b20ifX0.J6rX8H6roDa4Fq62vhlXtm702V9YnhqT2JLts31Jy2wvn9h5Qf-FxHInrGlQyHWqtPcM_mxFlgcTNYfZNNyuzF_5Iz-v6rKtCXK7tmtaw6qKSM3sDQAvGpPBRVuhxVxUUqgXkT6DeznfFTYOoD96P912jFF6KroObLtJfDJsfhvncaSqh3pcMbKUP6gwe05Xyg6g_psY48OpYjia6X9b0Hn1orgdOH15JE4SWM5nk0XXcbs98qlNKNu2SbmgrQqu9zv-3aiC44LWAp_7UTDLQvXTTpVk4GbmXNCoD26bOwPm75tm7b2X4yq5d4WAvkBQmTI5-ug1Kf7ZZyPtl1X7kw',
            ],
            "userTokens" => [
                "refreshToken" => 'AOvuKvSE2xaJWm1_PrywhdkuRYj6X-ZhhExvU6-sUBhSPDNmPDBBjh2lZePDqbegdbEoRXAuFciFkxrL5y7VU7dmX_ynXhQEsBYm2nlIaDaNedHCPJ2_bhkriQ3xuZAGaljSbzNFMiNCLp46X2yJ2bDbJHEXa2TtA1K1Mqm-SJYG7fQymEvguLSjXdiQtH6IexYtfiRs-09NzR3NqUfSMAmWjH1eZkO4cg","jwt":"eyJhbGciOiJSUzI1NiIsImtpZCI6IjRlMDBlOGZlNWYyYzg4Y2YwYzcwNDRmMzA3ZjdlNzM5Nzg4ZTRmMWUiLCJ0eXAiOiJKV1QifQ.eyJpc3MiOiJodHRwczovL3NlY3VyZXRva2VuLmdvb2dsZS5jb20vcHJlc3Rhc2hvcC1uZXdzc28tdGVzdGluZyIsImF1ZCI6InByZXN0YXNob3AtbmV3c3NvLXRlc3RpbmciLCJhdXRoX3RpbWUiOjE2MTYwNjM5MzYsInVzZXJfaWQiOiJ4UmNYM3N3cDMzU1pyZTFaY1UzMUVzV3hWUTcyIiwic3ViIjoieFJjWDNzd3AzM1NacmUxWmNVMzFFc1d4VlE3MiIsImlhdCI6MTYxNjA2MzkzNiwiZXhwIjoxNjE2MDY3NTM2LCJlbWFpbCI6ImF0b3VybmVyaWVAdGVzdC5jb20iLCJlbWFpbF92ZXJpZmllZCI6ZmFsc2UsImZpcmViYXNlIjp7ImlkZW50aXRpZXMiOnsiZW1haWwiOlsiYXRvdXJuZXJpZUB0ZXN0LmNvbSJdfSwic2lnbl9pbl9wcm92aWRlciI6ImN1c3RvbSJ9fQ.ce8a3Du_rKuZadWHLN5anzYyWimf1K8dzNqGs-kB8YqpUoi2SZ8eu3yAGTE-j42GDTF9UjK3-_mWkd_3vl5fLBxter63pB_dNDgG5jQ48VAD6ONhd39dqAKzCt0hHvC05NU7nb-FhxA61w6i69qQer4HAi0i0D5XJku9FOt1xkyjIxXf-rlGk8PxQilJaVLQYKT-wZ_SPj5EPYJ0qNDXzTY4AzFTp3E2tw7irBP3Ht-hfqYgmNsXSxAoOaJXIcKSRkiR8n_IiLhbnz4yRXbVc_Eut5ypn0HGWo00KgmxwumOnZ0OmCg2PNELj1WVeuAuV0T_IEp85cr24nz1fxlrHw',
            ],
        ];

        $base64 = $this->encodePayload($payload);

        //echo $base64;

        /** @var ShopKeysService $service */
        $service = $this->module->getService(ShopKeysService::class);

        $json = json_decode($service->decrypt(base64_decode($base64)), true);

        $this->assertArraySubset($json, $payload);
    }
}
