<?php

namespace PrestaShop\Module\PsAccounts\Tests\Feature;

use Db;
use PrestaShop\Module\PsAccounts\Http\Client\Response;
use PrestaShop\Module\PsAccounts\Provider\RsaKeysProvider;
use PrestaShop\Module\PsAccounts\Repository\UserTokenRepository;
use PrestaShop\Module\PsAccounts\Tests\HttpTestClient;
use PrestaShop\Module\PsAccounts\Tests\TestCase;
use PrestaShop\Module\PsAccounts\Vendor\Lcobucci\JWT\Builder;
use PrestaShop\Module\PsAccounts\Vendor\Lcobucci\JWT\Signer\Hmac\Sha256;
use PrestaShop\Module\PsAccounts\Vendor\Lcobucci\JWT\Signer\Key;
use PrestaShop\Module\PsAccounts\Vendor\Lcobucci\JWT\Token;

class FeatureTestCase extends TestCase
{
    /**
     * @var bool
     */
    protected $enableTransactions = false;

    /**
     * @var HttpTestClient
     */
    protected $client;

    /**
     * @var RsaKeysProvider
     */
    protected $rsaKeysProvider;

    /**
     * @var UserTokenRepository
     */
    protected $userTokenRepository;

    /**
     * @return void
     *
     * @throws \Exception
     */
    public function set_up()
    {
        parent::set_up();

        $scheme = $this->configuration->get('PS_SSL_ENABLED') ? 'https://' : 'http://';
        $domain = $this->configuration->get('PS_SHOP_DOMAIN');
        $baseUrl = $scheme . $domain . '/';

        $this->client = new HttpTestClient([
            'baseUri' => $baseUrl,
            'headers' => [
                'Accept' => 'application/json',
            ],
            'sslCheck' => $this->module->getParameter('ps_accounts.check_api_ssl_cert'),
            'timeout' => 60,
            'allowRedirects' => true,
        ], true);

        // FIXME: Link::getModuleLink
        // FIXME: OR activate friendly urls
        //$this->configuration->set('PS_REWRITING_SETTINGS', '1');

        $this->module->getLogger()->debug('Using ' . get_class($this->client));

        $this->rsaKeysProvider = $this->module->getService(RsaKeysProvider::class);
        $this->rsaKeysProvider->regenerateKeys();

        $this->userTokenRepository = $this->module->getService(UserTokenRepository::class);
        $this->userTokenRepository->cleanupCredentials();
    }

    /**
     * @param array $payload
     *
     * @return Token
     */
    public function encodePayload(array $payload)
    {
        //return base64_encode($shopKeysService->encrypt(json_encode($payload)));

        $builder = (new Builder());

        foreach ($payload as $k => $v) {
            $builder->withClaim($k, $v);
        }

        return $builder->getToken(
            new Sha256(),
            new Key($this->rsaKeysProvider->getPublicKey())
        );
    }

    /**
     * @param Response $response
     *
     * @return void
     */
    public function assertResponseOk($response)
    {
        $this->assertEquals(200, $response->statusCode);
    }

    /**
     * @param Response $response
     *
     * @return void
     */
    public function assertResponseCreated($response)
    {
        $this->assertEquals(201, $response->statusCode);
    }

    /**
     * @param Response $response
     *
     * @return void
     */
    public function assertResponseDeleted($response)
    {
        $this->assertEquals(204, $response->statusCode);
    }

    /**
     * @param Response $response
     *
     * @return void
     */
    public function assertResponseUnauthorized($response)
    {
        $this->assertEquals(401, $response->statusCode);
    }

    /**
     * @param Response $response
     *
     * @return void
     */
    public function assertResponseNotFound($response)
    {
        $this->assertEquals(404, $response->statusCode);
    }

    /**
     * @param Response $response
     *
     * @return void
     */
    public function assertResponseBadRequest($response)
    {
        $this->assertEquals(400, $response->statusCode);
    }

    /**
     * @param Response $response
     *
     * @return void
     */
    public function assertResponseError($response)
    {
        $this->assertGreaterThanOrEqual(500, $response->statusCode);
    }

    /**
     * @param Response $response
     *
     * @return array|mixed
     */
    public function getResponseJson($response)
    {
        return $response->body;
    }

    /**
     * @param string $controller
     *
     * @return string
     */
    public function getPageLink($controller, $module='ps_accounts')
    {
        // /module/ps_accounts/apiV1ShopUrl
        $scheme = $this->configuration->get('PS_SSL_ENABLED') ? 'https://' : 'http://';
        $domain = $this->configuration->get('PS_SHOP_DOMAIN');
        $baseUrl = $scheme . $domain;
        $url = $baseUrl . '/index.php?module=' . $module . '&fc=module&controller=' . $controller;

        return $url;
    }
}
