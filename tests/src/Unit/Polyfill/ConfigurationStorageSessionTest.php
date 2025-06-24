<?php

namespace PrestaShop\Module\PsAccounts\Tests\Unit\Repository\ConfigurationRespository;

use PrestaShop\Module\PsAccounts\Polyfill\ConfigurationStorageSession;
use PrestaShop\Module\PsAccounts\Tests\TestCase;

class ConfigurationStorageSessionTest extends TestCase
{
    /**
     * @var ConfigurationStorageSession
     */
    private $session;

    public function set_up()
    {
        parent::set_up();

        if (version_compare(_PS_VERSION_, '1.7.0.0', '>=')) {
            $this->markTestSkipped('== PrestaShop 1.6 Only Test ==');
        }

        // Login context
        \Context::getContext()->employee = new \Employee(1);
        \Context::getContext()->cookie = new \Cookie('cookie');
        \Context::getContext()->cookie->employee_id = 1;

        $this->session = new ConfigurationStorageSession($this->configuration);
    }

    /**
     * @test
     */
    public function itShouldSetSessionName()
    {
        $this->session->start();

        $this->assertEquals('PS_ACCOUNTS_SESSION_' . 1, $this->session->getName());
    }

    /**
     * @test
     */
    public function itShouldGetStartedStatus()
    {
        $this->assertFalse($this->session->isStarted());

        $this->session->start();

        $this->assertTrue($this->session->isStarted());
    }


    /**
     * @test
     */
    public function itShouldGetSessionId()
    {
        $this->session->start();

        $this->assertIsString($this->session->getId());
    }

    /**
     * @test
     */
    public function itShouldSetAndGetSessionProperty()
    {
        $this->session->start();

        $this->session->set('foo', 'bar');

        $this->assertEquals('bar', $this->session->get('foo'));
    }

    /**
     * @test
     */
    public function itShouldGetPropertyDefault()
    {
        $this->session->start();

        $this->assertEquals('default', $this->session->get('tata', 'default'));
    }

    /**
     * @test
     */
    public function itShouldClearSession()
    {
        $this->session->start();

        $this->session->set('foo', 'bar');

        $this->session->clear();

        $this->assertNull($this->session->get('foo'));
    }

    /**
     * @test
     */
    public function itShouldCleanupSessionOnStart()
    {
        $this->session->start();

        $this->session->set('foo', 'bar');

        $all = $this->session->all();

        $this->assertArrayHasKey('foo', $all);

        $this->session->setId('');

        $this->session->start();

        $this->session->set('bar', 'baz');

        $all = $this->session->all();

        $this->assertArrayHasKey('bar', $all);
        $this->assertArrayNotHasKey('foo', $all);
    }

    /**
     * @test
     */
    public function itShouldGetAllProperties()
    {
        $this->session->start();

        $this->session->set('foo', 'bar');
        $this->session->set('bar', 'baz');

        $this->assertArraySubset([
            'foo' => 'bar',
            'bar' => 'baz',
        ], $this->session->all());
    }

    /**
     * @test
     */
    public function itShouldMaintainOneSessionByEmployee()
    {
        $this->session->start();

        $this->session->set('foo', 'bar');

        $this->session->setId('');

        $this->session->start();

        $this->session->set('foo', 'bar');

        $this->session->setId('');

        $this->session->start();

        $this->session->set('foo', 'bar');

        $this->session->setId('');

        $this->session->start();

        $this->session->set('foo', 'bar');

        $count = \Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . "configuration WHERE name LIKE '" . $this->session->getName() . "_%'"
        );

        $this->assertEquals(1, $count);
    }

    /**
     * @test
     */
    public function itShouldThrowExceptionOnUnimplementedMethod()
    {
        $this->expectException(\Exception::class);

        $this->session->getBag('random');
    }

    public function tear_down()
    {
        parent::tear_down();

        $this->session->clear();
    }
}
