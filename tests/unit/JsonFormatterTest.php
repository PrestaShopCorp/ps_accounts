<?php

use PHPUnit\Framework\TestCase;
use PrestaShop\Module\PsAccounts\Formatter\JsonFormatter;

class JsonFormatterTest extends TestCase
{
    /**
     * @var JsonFormatter
     */
    private $jsonFormatter;

    protected function setUp()
    {
        parent::setUp();
        $this->jsonFormatter = new JsonFormatter();
    }

    public function testFormatNewlineJsonString()
    {
        $data = [
            ['test' => 'data'],
            ['test2' => 'data2'],
        ];

        $this->assertTrue(is_string($this->jsonFormatter->formatNewlineJsonString($data)));
        $this->assertRegExp("/\r\n/",$this->jsonFormatter->formatNewlineJsonString($data));
    }
}
