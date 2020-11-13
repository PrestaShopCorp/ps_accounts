<?php

namespace PrestaShop\Module\PsAccounts\Tests;

use Db;
use PrestaShop\Module\PsAccounts\Adapter\Configuration;

class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Faker\Generator
     */
    public $faker;

    /**
     * @var array
     */
    private $config;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
    }

    /**
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();

        Db::getInstance()->execute('START TRANSACTION');

        $this->faker = \Faker\Factory::create();
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
     * @param array $valueMap
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    public function getConfigurationMock(array $valueMap)
    {
        if (!$this->config) {
            $this->config = $valueMap;
        }

        //$configuration = $this->createMock(Configuration::class);
        $configuration = $this->getMockBuilder(Configuration::class)
            ->setConstructorArgs([\Context::getContext()])
            ->getMock();

        $configuration->method('get')
            ->will($this->returnCallback(function ($key, $default = false) {
                foreach ($this->config as $map) {
                    $return = array_pop($map);
                    if ([$key, $default] === $map) {
                        return $return;
                    }
                }

                return null;
            }));
        //->will($this->returnValueMap($valueMap));

        $configuration->method('set')
            ->will($this->returnCallback(function ($key, $values, $html = false) use ($configuration) {
                foreach ($this->config as &$row) {
                    if ($row[0] == $key) {
                        $row[2] = (string) $values;

                        return;
                    }
                }
                $this->config[] = [$key, false, (string) $values];
            }));

        return $configuration;
    }
}
