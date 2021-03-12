<?php

namespace PrestaShop\Module\PsAccounts\Tests;

use Db;
use Faker\Generator;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Token;
use Module;
use Ps_accounts;

class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Generator
     */
    public $faker;

    /**
     * @var Ps_accounts
     */
    public $module;

    /**
     * @var \PrestaShop\Module\PsAccounts\Adapter\Configuration;
     */
    public $configuration;

    /**
     * @return void
     *
     * @throws \Exception
     */
    protected function setUp()
    {
        parent::setUp();

        Db::getInstance()->execute('START TRANSACTION');

        $this->faker = \Faker\Factory::create();

        $this->module = Module::getInstanceByName('ps_accounts');

        $this->configuration = $this->module->getService(
            \PrestaShop\Module\PsAccounts\Adapter\Configuration::class
        );
    }

    /**
     * @return void
     */
    public function tearDown()
    {
        Db::getInstance()->execute('ROLLBACK');

        parent::tearDown();
    }

    /**
     * @param \DateTimeImmutable|null $expiresAt
     * @param array $claims
     *
     * @return Token
     */
    public function makeJwtToken(\DateTimeImmutable $expiresAt = null, array $claims = [])
    {
        $builder = (new Builder())->expiresAt($expiresAt);

        foreach ($claims as $claim => $value) {
            $builder->withClaim($claim, $value);
        }

        $configuration = Configuration::forUnsecuredSigner();

        return $builder->getToken(
            $configuration->signer(),
            $configuration->signingKey()
        );
    }
}
