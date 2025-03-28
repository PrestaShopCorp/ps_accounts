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

class BaseTestCase extends \PHPUnit\Framework\TestCase
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
     * @inject
     *
     * @var \PrestaShop\Module\PsAccounts\Cqrs\CommandBus
     */
    public $commandBus;

    /**
     * @inject
     *
     * @var \PrestaShop\Module\PsAccounts\Adapter\Configuration
     */
    public $configuration;

    /**
     * @inject
     *
     * @var \PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository
     */
    public $configurationRepository;

    /**
     * @inject
     *
     * @var \PrestaShop\Module\PsAccounts\Account\LinkShop
     */
    public $linkShop;

    /**
     * @var bool
     */
    protected $enableTransactions = true;

    /**
     * @var ServiceProperty[]
     */
    protected $replacedProperties = [];

    /**
     * @return void
     *
     * @throws \Exception
     */
    protected function set_up()
    {
        // Don't remove this line
        \Configuration::clearConfigurationCacheForTesting();

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
    public function tear_down()
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

        $this->restoreProperties();

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
        $issuedAt = new \DateTimeImmutable();

        $builder = (new Builder())
            ->issuedAt($issuedAt)
            ->expiresAt($expiresAt);

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
     *
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
     * FIXME: hard dependency with non public members
     *
     * @param mixed $object
     * @param string $propertyName
     * @param mixed $replacement
     *
     * @return void
     *
     * @throws \ReflectionException
     */
    protected function replaceProperty($object, $propertyName, $replacement)
    {
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        $this->setPropertyToRestore($object, $propertyName, $property->getValue($object));
        $property->setValue($object, $replacement);
    }

    /**
     * @param mixed $object
     * @param string $propertyName
     * @param mixed $originalValue
     *
     * @return void
     */
    protected function setPropertyToRestore($object, $propertyName, $originalValue)
    {
        $prop = new ServiceProperty();
        $prop->object = $object;
        $prop->propertyName = $propertyName;
        $prop->originalValue = $originalValue;
        $this->replacedProperties[] = $prop;
    }

    /**
     * @return void
     *
     * @throws \ReflectionException
     */
    protected function restoreProperties()
    {
        foreach ($this->replacedProperties as $property) {
            $this->replaceProperty(
                $property->object,
                $property->propertyName,
                $property->originalValue
            );
        }
        $this->replacedProperties = [];
    }

    /**
     * @param array $subset
     * @param array $array
     * @param string $message
     * @param bool $markTestIncomplete
     *
     * @return void
     */
    protected function assertBodySubset(
        $subset,
        $array,
        $message = '',
        $markTestIncomplete = false
    ) {
        if (!$markTestIncomplete || is_array($array) && !empty($array)) {
            $this->assertArraySubset($subset, $array, $message);
        } else {
            $this->markTestIncomplete('WARNING: Cannot evaluate response [body is empty]');
        }
    }

    /**
     * @param array $subset
     * @param array $array
     * @param string $message
     *
     * @return void
     */
    protected function assertBodySubsetOrMarkAsIncomplete($subset, $array, $message = '')
    {
        $this->assertBodySubset($subset, $array, $message, true);
    }
}
