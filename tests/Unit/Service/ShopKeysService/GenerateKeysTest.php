<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Service\ShopKeysService;

use Db;
use Module;
use PrestaShop\Module\PsAccounts\Exception\SshKeysNotFoundException;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
use PrestaShop\Module\PsAccounts\Service\PsAccountsService;
use PrestaShop\Module\PsAccounts\Service\ShopKeysService;
use PrestaShop\Module\PsAccounts\Tests\TestCase;
use Ps_accounts;

class GenerateKeysTest extends TestCase
{
    /**
     * @test
     *
     * @throws SshKeysNotFoundException
     */
    public function it_should_create_rsa_keys()
    {
        /** @var ShopKeysService $service */
        $service = $this->module->getService(ShopKeysService::class);

        /** @var ConfigurationRepository $configuration */
        $configuration = $this->module->getService(ConfigurationRepository::class);

        //echo "A\n" . $configuration->getAccountsRsaPrivateKey() . "\n";

        // Empty DB
        $configuration->updateAccountsRsaPrivateKey(null);
        $configuration->updateAccountsRsaPublicKey(null);
        $configuration->updateAccountsRsaSignData(null);

        $this->assertEmpty($configuration->getAccountsRsaPrivateKey());
        $this->assertEmpty($configuration->getAccountsRsaPublicKey());
        $this->assertEmpty($configuration->getAccountsRsaSignData());

        $service->generateKeys();

        //echo "B\n" . $configuration->getAccountsRsaPrivateKey() . "\n";

        $this->assertNotEmpty($configuration->getAccountsRsaPrivateKey());
        $this->assertNotEmpty($configuration->getAccountsRsaPublicKey());
        $this->assertNotEmpty($configuration->getAccountsRsaSignData());

        $data = $this->faker->sentence();
        $signedData = $service->signData($configuration->getAccountsRsaPrivateKey(), $data);

        $this->assertTrue(
            $service->verifySignature(
                $configuration->getAccountsRsaPublicKey(),
                $signedData,
                $data
            )
        );
    }

    /**
     * @test
     */
    public function it_should_generate_key_pair()
    {
        /** @var ShopKeysService $service */
        $service = $this->module->getService(ShopKeysService::class);

        $key = $service->createPair();
        $this->assertArrayHasKey('privatekey', $key, "Key 'privatekey' don't exist in Array");
        $this->assertArrayHasKey('publickey', $key, "Key 'publickey' don't exist in Array");
        $this->assertEquals('string', gettype($key['privatekey']), "'privatekey' isn't string");
        $this->assertEquals('string', gettype($key['publickey']), "'privatekey' isn't string");
    }

    /**
     * @test
     */
    public function it_should_verify_signature()
    {
        /** @var ShopKeysService $service */
        $service = $this->module->getService(ShopKeysService::class);

        $key = $service->createPair();

        $privateKey = '-----BEGIN RSA PRIVATE KEY-----
         MIICWwIBAAKBgQCeksA2G79u1InvLc8tKcerLCLa66be0h/CD9RhDnQh5CXQxe5H
         URMyTWy6DpyFyddg6cnOh1RavMWUvdvjwtcgxVmmwtBA7kS8sKuxRUBFHjxB7i9N
         cLlbhBTQl15zjpHcI7ggBulqTS1b5jwEuZSv8d+NW0pCTZk/4Xm4d2i+9QIDAQAB
         AoGAGkMnvk5eKBbfOVOW6l3vCbRnmWZJ3sFiLRu+Cs0AAtTsRmVhj0IoMb6M8UuW
         NLo3B3/wwlm7aMO23WmMT25nfm0ozMD5JBhsHhMjNf936+eul+brSL0yw3OBWHrn
         rhRAibzy3Oe7lHqhJseGPddb7k3rrYHiCL3XjD4aUnSqxokCQQDQ4jOaP75srmWw
         drR4NbJy18+BOQOKLew0mDdMwfeCskWEiFDftRTSlOFtcG4p8MhsT5XKeFGKzrEe
         fYPqnuZbAkEAwldsIp+UWOudQb6/sqCLyrPYtH5K1SZs2eqaauuFVz0tTMONznaa
         3QESWSqPNjVXmtZQNh64lR9SrGBugB4Q7wJACol+pOdWSdE6W/6A+BdtWxG76/7e
         SNgsNDMBhyO5wqQPkbH2snJGDKFqBcVIKWF2GtCg88fCBUiL8sfOIcXGRQJAFpBB
         3M88UQqiCnUUGrArKtCws1wKYi8A6lgjr5BCvfs7XDNELpl0p34tXC7ly7xrvG1v
         iKkOczncxmi3y6Yx/wJABjuZp1et+/uNQ33vv/NlHRNqfR4B/ZVbn+GvdAiuDEdU
         3+trKNmdcTb/7oQ/5RygDWjjXVvaZhheA3LCHFwiSg==
         -----END RSA PRIVATE KEY-----';

        $publicKey = 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAAAgQCeksA2G79u1InvLc8tKcerLCLa66be0h/CD9RhDnQh5CXQxe5HURMyTWy6DpyFyddg6cnOh1RavMWUvdvjwtcgxVmmwtBA7kS8sKuxRUBFHjxB7i9NcLlbhBTQl15zjpHcI7ggBulqTS1b5jwEuZSv8d+NW0pCTZk/4Xm4d2i+9Q== phpseclib-generated-key';
        $data = 'hmac';
        $signature = $service->signData($privateKey, $data);

        $this->assertEquals(1, $service->verifySignature($publicKey, $signature, $data), "Data doesn't signed");
    }
}
