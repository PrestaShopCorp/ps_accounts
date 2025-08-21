<?php

namespace PrestaShop\Module\PsAccounts\Tests\Feature\AdminAjaxV2PsAccountsController;

use PrestaShop\Module\PsAccounts\Http\Client\Request;
use PrestaShop\Module\PsAccounts\Http\Controller\AbstractV2RestController;
use PrestaShop\Module\PsAccounts\Provider\ShopProvider;
use PrestaShop\Module\PsAccounts\Service\AdminTokenService;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;
use PrestaShop\Module\PsAccounts\Tests\Feature\TestCase;

class GetContextTest extends TestCase
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
     * @var PsAccountsService
     */
    protected $psAccountsService;

    /**
     * @inject
     *
     * @var AdminTokenService
     */
    protected $tokenService;

    /**

    {
        "ps_accounts": {
        "last_succeeded_upgrade_version": "8.0.0",
        "module_version_from_files": "8.0.0"
        },
        "groups": [
                {
                    "id": 1,
                    "name": "Default",
                    "shops": [
                        {
                            "id": 1,
                            "name": "PrestaShop",
                            "backOfficeUrl": "https:\/\/shop.prestashop.local\/toto\/admin-dev",
                            "frontendUrl": "https:\/\/shop.prestashop.local\/toto",
                            "identifyPointOfContactUrl": "https:\/\/shop.prestashop.local\/toto\/admin-dev\/index.php?controller=AdminOAuth2PsAccounts&action=identifyPointOfContact&source=ps_accounts",
                            "shopStatus": {
                            "cloudShopId": "85dc36df-9578-4dd1-b67f-0f6ddfe3006b",
                                "isVerified": true,
                                "frontendUrl": "https:\/\/shop.prestashop.local\/toto",
                                "backofficeUrl": null,
                                "shopVerificationErrorCode": "",
                                "pointOfContactUuid": null,
                                "pointOfContactEmail": null,
                                "createdAt": "2025-08-19T16:07:11+00:00",
                                "updatedAt": "2025-08-19T16:07:12+00:00",
                                "verifiedAt": "2025-08-19T16:07:12+00:00",
                                "unverifiedAt": null,
                                "backOfficeUrl": "https:\/\/shop.prestashop.local\/toto\/admin-dev"
                            },
                            "fallbackCreateIdentityUrl": "https:\/\/shop.prestashop.local\/toto\/admin-dev\/index.php?controller=AdminAjaxV2PsAccounts&ajax=1&action=fallbackCreateIdentity&shop_id=1&source=ps_accounts"
                        }
                    ]
                }
            ]
        }
    */

    /**
     * @test
     */
    public function itShouldRespondWithValidContext()
    {
        $shop = $this->shopProvider->formatShopData((array) \Shop::getShop(1));

        $url = $this->psAccountsService->getContextUrl();
        //$url = str_replace('http://', 'https://', $url);
        //$url = '/index.php?controller=AdminAjaxV2PsAccounts&ajax=1&action=getContext';
        //$url = '/admin-dev/?controller=AdminAjaxV2PsAccounts&ajax=1&action=getContext&source=ps_accounts';
        $token = (string)$this->tokenService->getToken();

        $response = $this->client->get($url, [
            Request::HEADERS => [
                AbstractV2RestController::HEADER_AUTHORIZATION => 'Bearer ' . $token,
            ],
        ]);

        $this->assertResponseOk($response);

        $json = $this->getResponseJson($response);

        $this->assertIsArray($json['ps_accounts']);

        $this->assertIsArray($json['groups']);
        $this->assertNotEmpty($json['groups']);

        $shops = $json['groups'][0]['shops'][0];

        $this->assertEquals($shop->id, $shops['id']);
        $this->assertEquals($shop->name, $shops['name']);
    }

    /**
     * @test
     */
    public function itShouldFailWithoutToken()
    {
        $url = $this->psAccountsService->getContextUrl();

        $response = $this->client->get($url);

        $this->assertResponseUnauthorized($response);
    }

    /**
     * @test
     */
    public function itShouldFailWithWrongToken()
    {
        $url = $this->psAccountsService->getContextUrl();

        $token = $this->makeJwtToken(new \DateTimeImmutable('+1 hour'));

        $response = $this->client->get($url, [
            Request::HEADERS => [
                AbstractV2RestController::HEADER_AUTHORIZATION => 'Bearer ' . $token,
            ],
        ]);

        $this->assertResponseUnauthorized($response);
    }

    /**
     * @test
     */
    public function itShouldRespondWithAccessControlHeadersWithAllowedOrigin()
    {
        //$shop = $this->shopProvider->formatShopData((array) \Shop::getShop(1));

        $url = $this->psAccountsService->getContextUrl();
        $token = (string)$this->tokenService->getToken();
        $allowedOrigin = $this->module->getParameter('ps_accounts.cors_allowed_origins')[0];

        $response = $this->client->get($url, [
            Request::HEADERS => [
                "Origin" => $allowedOrigin,
                //"Origin" => "https://foo.com",
                AbstractV2RestController::HEADER_AUTHORIZATION => 'Bearer ' . $token,
            ],
        ]);

        //print_r($response->headers);

        $this->assertResponseOk($response);

        $json = $this->getResponseJson($response);

        $this->assertIsArray($json['ps_accounts']);

        $this->assertEquals($allowedOrigin, $response->headers['Access-Control-Allow-Origin']);
    }


    /**
     * @test
     */
    public function itShouldRespondWithoutAccessControlHeadersWithNotAllowedOrigin()
    {
        //$shop = $this->shopProvider->formatShopData((array) \Shop::getShop(1));

        $url = $this->psAccountsService->getContextUrl();
        $token = (string)$this->tokenService->getToken();
        $NotAllowedOrigin = "https://foo.com";

        $response = $this->client->get($url, [
            Request::HEADERS => [
                "Origin" => $NotAllowedOrigin,
                AbstractV2RestController::HEADER_AUTHORIZATION => 'Bearer ' . $token,
            ],
        ]);

        //print_r($response->headers);

        $this->assertResponseOk($response);

        $json = $this->getResponseJson($response);

        $this->assertIsArray($json['ps_accounts']);

        $this->assertArrayNotHasKey($NotAllowedOrigin, $response->headers);
    }
}
