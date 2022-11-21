<?php

namespace PrestaShop\Module\PsAccounts\Tests\Feature;

use Db;
use GuzzleHttp\Client;
use GuzzleHttp\Message\ResponseInterface;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key;
use PrestaShop\Module\PsAccounts\Provider\RsaKeysProvider;
use PrestaShop\Module\PsAccounts\Repository\UserTokenRepository;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

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
     * @var RsaKeysProvider
     */
    protected $rsaKeysProvider;

    /**
     * @var UserTokenRepository
     */
    protected $userTokenRepository;

    /**
     * @throws \Exception
     */
    public function setUp()
    {
        parent::setUp();

        $scheme = $this->configuration->get('PS_SSL_ENABLED') ? 'https://' : 'http://';
        $domain = $this->configuration->get('PS_SHOP_DOMAIN');
        $baseUrl = $scheme . $domain;

        $this->client = new Client([
            'base_url' => $baseUrl,
            'defaults' => [
                'verify' => false,
                'timeout' => 60,
                'exceptions' => false,
                'allow_redirects' => false,
                'query' => [],
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ],
        ]);

        $this->rsaKeysProvider = $this->module->getService(RsaKeysProvider::class);
        $this->rsaKeysProvider->regenerateKeys();

        $this->userTokenRepository = $this->module->getService(UserTokenRepository::class);
        $this->userTokenRepository->cleanupCredentials();

        // FIXME: Link::getModuleLink
        // FIXME: OR activate friendly urls
        //$this->configuration->set('PS_REWRITING_SETTINGS', '1');
    }

    /**
     * @param array $payload
     *
     * @return \Lcobucci\JWT\Token
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
            new Key($this->rsaKeysProvider->getPublicKey())
        );
    }

    /**
     * @param ResponseInterface $response
     *
     * @return void
     */
    public function assertResponseOk(ResponseInterface $response)
    {
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @param ResponseInterface $response
     *
     * @return void
     */
    public function assertResponseCreated(ResponseInterface $response)
    {
        $this->assertEquals(201, $response->getStatusCode());
    }

    /**
     * @param ResponseInterface $response
     *
     * @return void
     */
    public function assertResponseDeleted(ResponseInterface $response)
    {
        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @param ResponseInterface $response
     *
     * @return void
     */
    public function assertResponseUnauthorized(ResponseInterface $response)
    {
        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * @param ResponseInterface $response
     *
     * @return void
     */
    public function assertResponseNotFound(ResponseInterface $response)
    {
        $this->assertEquals(404, $response->getStatusCode());
    }
}
