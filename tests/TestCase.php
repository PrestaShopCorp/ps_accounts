<?php

namespace PrestaShop\Module\PsAccounts\Tests;

use Db;
use Exception;
use Faker\Generator;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Token;
use Module;
use PrestaShop\Module\PsAccounts\Adapter\Configuration as ConfigurationAdapter;
use PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository;
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
     * @var ConfigurationAdapter
     */
    public $configuration;

    /**
     * @var ConfigurationRepository
     */
    public $configurationRepository;

    /**
     * @var bool
     */
    protected $enableTransactions = true;

    /**
     * @return void
     *
     * @throws \Exception
     */
    protected function setUp()
    {
        parent::setUp();

        if (true === $this->enableTransactions) {
            $this->startTransaction();
        }

        $this->faker = \Faker\Factory::create();

        $this->module = $this->getModuleInstance();

        $this->configuration = $this->module->getService(
            ConfigurationAdapter::class
        );

        $this->configurationRepository = $this->module->getService(
            ConfigurationRepository::class
        );
    }

    /**
     * @return void
     */
    public function tearDown()
    {
        $this->rollback();

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

    /**
     * @param \DateTimeImmutable|null $expiresAt
     * @param array $claims
     *
     * @return Token
     *
     * @throws \Exception
     */
    public function makeFirebaseToken(\DateTimeImmutable $expiresAt = null, array $claims = [])
    {
        if (null === $expiresAt) {
            $expiresAt = new \DateTimeImmutable('tomorrow');
        }
        return $this->makeJwtToken($expiresAt, array_merge([
            'sub' => $this->faker->uuid,
            'email' => $this->faker->safeEmail,
            'email_verified' => $this->faker->boolean,
        ], $claims));
    }

    /**
     * @return void
     */
    public function startTransaction()
    {
        Db::getInstance()->execute('START TRANSACTION');
    }

    /**
     * @return void
     */
    public function rollback()
    {
        Db::getInstance()->execute('ROLLBACK');
    }

    /**
     * @return Ps_accounts
     *
     * @throws Exception
     */
    private function getModuleInstance()
    {
        /** @var Ps_accounts|false $module */
        $module = Module::getInstanceByName('ps_accounts');

        if ($module === false) {
            throw new Exception('Module not installed');
        }

        return $module;
    }
}
