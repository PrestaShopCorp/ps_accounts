<?php

namespace PrestaShop\Module\PsAccounts\Tests;

use Db;
use Module;
use Faker\Generator;
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
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();

        Db::getInstance()->execute('START TRANSACTION');

        $this->faker = \Faker\Factory::create();

        /** @var Ps_accounts $module */
        $this->module = Module::getInstanceByName('ps_accounts');
    }

    /**
     * @return void
     */
    public function tearDown()
    {
        Db::getInstance()->execute('ROLLBACK');

        parent::tearDown();
    }
}
