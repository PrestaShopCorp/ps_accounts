<?php

namespace PrestaShop\Module\PsAccounts\Tests;

use Db;
use Exception;
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
     * @buildService
     * @var \PrestaShop\Module\PsAccounts\Adapter\Configuration
     */
    public $configuration;

    /**
     * @buildService
     * @var \PrestaShop\Module\PsAccounts\Repository\ConfigurationRepository
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

        $this->buildClassProperties();
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
     * @param $className
     *
     * @return string
     */
    protected function lcClassName($className)
    {
        return lcfirst(preg_replace('/^.*\\\\/', '', $className));
    }

    /**
     * @param array $services
     *
     * @return void
     *
     * @throws \Exception
     */
    protected  function buildServices(array $services = [])
    {
        array_walk($services, function ($class) {
            if (is_array($class)) {
                $propName = array_keys($class)[0];
                $class = $class[$propName];
            } else {
                $propName = $this->lcClassName($class);
            }
            $this->$propName = $this->module->getService($class);
        });
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    protected function buildClassProperties($tag = 'buildService')
    {
        $mirror = new \ReflectionClass($this);
        $props = $mirror->getProperties();
        $classes = [];
        foreach ($props as $prop) {
            $doc = $prop->getDocComment();
            if (preg_match("/@$tag/", $doc)) {
                //echo $prop->name . ' => ';
                if (preg_match('/@var\s+([\w\\\\]+)/', $doc, $m)) {
                    $class = preg_replace('/^\\\\/', '', $m[1]);
                    //echo $class . "\n";
                    $classes[] = [$prop->name => $class];
                }
            }
        }
        $this->buildServices($classes);
    }
}
