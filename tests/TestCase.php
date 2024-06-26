<?php

namespace PrestaShop\Module\PsAccounts\Tests;

use Db;
use Exception;
use Faker\Generator;
use Module;
use PrestaShop\Module\PsAccounts\Account\Session\Firebase;
use PrestaShop\Module\PsAccounts\Account\Session\ShopSession;
use PrestaShop\Module\PsAccounts\Vendor\Lcobucci\JWT\Builder;
use PrestaShop\Module\PsAccounts\Vendor\Lcobucci\JWT\Configuration;
use PrestaShop\Module\PsAccounts\Vendor\Lcobucci\JWT\Token;
use Ps_accounts;

class TestCase extends \PHPUnit\Framework\TestCase
{
    use \DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;

    /**
     * @var Generator
     */
    public $faker;

    /**
     * @var Ps_accounts
     */
    public $module;

    /**
     * @inject
     * @var \PrestaShop\Module\PsAccounts\Cqrs\CommandBus
     */
    public $commandBus;

    /**
     * @inject
     * @var \PrestaShop\Module\PsAccounts\Adapter\Configuration
     */
    public $configuration;

    /**
     * @inject
     * @var \PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository
     */
    public $configurationRepository;

    /**
     * @inject
     * @var \PrestaShop\Module\PsAccounts\Account\LinkShop
     */
    public $linkShop;

    /**
     * @var bool
     */
    protected $enableTransactions = true;

    /**
     * @return void
     *
     * @throws \Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (true === $this->enableTransactions) {
            $this->startTransaction();
        }

        $this->faker = \Faker\Factory::create();

        $this->module = $this->getModuleInstance();

        (new ServiceInjector($this, function ($propName, $class) {
            $this->$propName = $this->module->getService($class);
        }))->resolveServices();
    }

    /**
     * @return void
     */
    public function tearDown(): void
    {
        $this->rollback();

        // FIXME: shouldn't every test class do its cleanup ?
        foreach ([
                     ShopSession::class,
                     Firebase\ShopSession::class,
                     Firebase\OwnerSession::class
                 ] as $class) {
            $this->module->getService($class)->resetRefreshTokenErrors();
        }

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

        if (isset($claims['sub'])) {
            $builder->relatedTo($claims['sub']);
            unset($claims['sub']);
        }

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
    protected function getModuleInstance()
    {
        /** @var Ps_accounts|false $module */
        $module = Module::getInstanceByName('ps_accounts');

        if ($module === false) {
            throw new Exception('Module not installed');
        }

        return $module;
    }

    /**
     * @param array $body
     * @param int $httpCode
     * @param bool $status
     *
     * @return array
     */
    protected function createApiResponse(array $body, $httpCode, $status)
    {
        return [
            'status' => $status,
            'httpCode' => $httpCode,
            'body' => $body,
        ];
    }

    /**
     * @param $class
     * @param $methods
     * @return \#o#Э#A#M#C\PrestaShop\Module\PsAccounts\Tests\TestCase.createMockWithMethods.0|(\#o#Э#A#M#C\PrestaShop\Module\PsAccounts\Tests\TestCase.createMockWithMethods.0&\PHPUnit_Framework_MockObject_MockObject)|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createMockWithMethods($class, $methods = [])
    {
        $mock = $this->createMock($class);
        foreach ($methods as $method => $return) {
            $mock->method($method)->willReturn($return);
        }
        return $mock;
    }

    /**
     * @param $classInstance
     * @param $dependencyName
     * @param $newDependency
     * @return void
     * @throws \ReflectionException
     */
    protected function replaceDependency($classInstance, $dependencyName, $newDependency)
    {
        $reflection = new \ReflectionClass($classInstance);
        $property = $reflection->getProperty($dependencyName);
        $property->setAccessible(true);
        $property->setValue($classInstance, $newDependency);
    }
}
