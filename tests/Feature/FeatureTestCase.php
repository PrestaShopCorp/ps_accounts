<?php

namespace PrestaShop\Module\PsAccounts\Tests\Feature;

use Db;
use GuzzleHttp\Client;
use GuzzleHttp\Message\ResponseInterface;
<<<<<<< HEAD
use PrestaShop\Module\PsAccounts\Http\Client\Guzzle\GuzzleClient;
use PrestaShop\Module\PsAccounts\Http\Client\Guzzle\GuzzleClientFactory;
use PrestaShop\Module\PsAccounts\Provider\RsaKeysProvider;
use PrestaShop\Module\PsAccounts\Repository\UserTokenRepository;
=======
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key;
use PrestaShop\Module\PsAccounts\Api\Client\Guzzle\AbstractGuzzleClient;
use PrestaShop\Module\PsAccounts\Api\Client\Guzzle\GuzzleClientFactory;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\OwnerSession;
use PrestaShop\Module\PsAccounts\Domain\Shop\Entity\PublicKey;
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)
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
     * @var Client
     */
    protected $client;

    /**
     * @var GuzzleClient
     */
    protected $guzzleClient;

    /**
     * @var PublicKey
     */
    protected $publicKey;

    /**
     * @var OwnerSession
     */
    protected $ownerSession;

    /**
     * @return void
     *
     * @throws \Exception
     */
    public function setUp()
    {
        parent::setUp();

        $scheme = $this->configuration->get('PS_SSL_ENABLED') ? 'https://' : 'http://';
        $domain = $this->configuration->get('PS_SHOP_DOMAIN');
        $baseUrl = $scheme . $domain . '/';

        $this->guzzleClient = (new GuzzleClientFactory())->create([
<<<<<<< HEAD
            'base_uri' => $baseUrl,
            'headers' => [
                'Accept' => 'application/json',
=======
            'base_url' => $baseUrl,
            'defaults' => [
                'timeout' => 10,
                'exceptions' => false,
                'allow_redirects' => false,
                'query' => [],
                'headers' => [
                    'Accept' => 'application/json',
                ],
>>>>>>> 6da8cbe1 (Refacto DDD-CQRS2)
            ],
            'verify' => false,
            'timeout' => 60,
            'http_errors' => false,
            //
            'allow_redirects' => false,
            'query' => [],
        ]);

        $this->module->getLogger()->debug('Using ' . get_class($this->guzzleClient));

        $this->client = $this->guzzleClient->getClient();

        $this->publicKey = $this->module->getService(PublicKey::class);
        $this->publicKey->regenerateKeys();

        $this->ownerSession = $this->module->getService(OwnerSession::class);
        $this->ownerSession->cleanup();

        // FIXME: Link::getModuleLink
        // FIXME: OR activate friendly urls
        //$this->configuration->set('PS_REWRITING_SETTINGS', '1');
    }

    /**
     * @param array $payload
     *
     * @return Token
     *
     * @throws \Exception
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
            new Key($this->publicKey->getPublicKey())
        );
    }

    /**
     * @param ResponseInterface $response
     *
     * @return void
     */
    public function assertResponseOk($response)
    {
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @param ResponseInterface $response
     *
     * @return void
     */
    public function assertResponseCreated($response)
    {
        $this->assertEquals(201, $response->getStatusCode());
    }

    /**
     * @param ResponseInterface $response
     *
     * @return void
     */
    public function assertResponseDeleted($response)
    {
        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @param ResponseInterface $response
     *
     * @return void
     */
    public function assertResponseUnauthorized($response)
    {
        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * @param ResponseInterface $response
     *
     * @return void
     */
    public function assertResponseNotFound($response)
    {
        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @param ResponseInterface $response
     *
     * @return void
     */
    public function assertResponseBadRequest($response)
    {
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @param ResponseInterface $response
     *
     * @return void
     */
    public function assertResponseError($response)
    {
        $this->assertGreaterThanOrEqual(500, $response->getStatusCode());
    }

    /**
     * @param $response
     *
     * @return array|mixed
     */
    public function getResponseJson($response)
    {
        return $this->guzzleClient->getResponseJson($response);
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
