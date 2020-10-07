<?php

use PHPUnit\Framework\TestCase;
use PrestaShop\Module\PsAccounts\Formatter\ArrayFormatter;

class ArrayFormatterTest extends TestCase
{
    /**
     * @var ArrayFormatter
     */
    private $arrayFormatter;

    protected function setUp()
    {
        $this->arrayFormatter = new ArrayFormatter();
    }

    public function testFormatArray()
    {
        $data = [
            'value1',
            'value2',
        ];

        $this->assertEquals('value1;value2', $this->arrayFormatter->formatArray($data));
        $this->assertEquals('value1:value2', $this->arrayFormatter->formatArray($data, ':'));
    }

    public function testFormatValueArrayTest()
    {
        $data = [
            ['id' => 1, 'value' => 123],
            ['id' => 2, 'value' => 456],
            ['id' => 3, 'value' => 789],
        ];

        $this->assertEquals('123;456;789', $this->arrayFormatter->formatValueArray($data));
        $this->assertEquals('123:456:789', $this->arrayFormatter->formatValueArray($data, ':'));
    }
}
