<?php

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Easybook\Tests\Publishers;

use Easybook\DependencyInjection\Application;
use Easybook\Events\ParseEvent;
use Easybook\Tests\TestCase;

class ParseEventTest extends TestCase
{
    public $app;
    public $event;
    public $item;

    public function setUp()
    {
        $this->item = $item = array(
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
            'compound_key_1' => 'value1',
            'compound_key_2' => 'value2',
            'compound_key_3' => 'value3',
        );

        $this->app = new Application();
        $this->app['publishing.active_item'] = $item;

        $this->event = new ParseEvent($this->app);
    }

    public function testGetItem()
    {
        $this->assertEquals($this->item, $this->event->getItem());
    }

    public function testSetItem()
    {
        $newItem = array_reverse($this->item);
        $this->event->setItem($newItem);

        $this->assertEquals($this->item, $this->event->getItem());
    }

    /**
     * @dataProvider getTestGetMethodData
     */
    public function testGetMethod($key, $expectedValue)
    {
        $this->assertEquals($expectedValue, $this->event->getItemProperty($key));
    }

    public function getTestGetMethodData()
    {
        return array(
            array('key1',           'value1'),
            array('key2',           'value2'),
            array('key3',           'value3'),
            array('compound_key_1', 'value1'),
            array('compound_key_2', 'value2'),
            array('compound_key_3', 'value3'),
        );
    }

    /**
     * @dataProvider getTestSetMethodData
     */
    public function testSetMethod($key, $expectedValue)
    {
        $this->event->setItemProperty($key, $expectedValue);

        $this->assertEquals($expectedValue, $this->event->getItemProperty($key));
    }

    public function getTestSetMethodData()
    {
        return array(
            array('key1',           'new_value1'),
            array('key2',           'new_value2'),
            array('key3',           'new_value3'),
            array('compound_key_1', 'new_value1'),
            array('compound_key_2', 'new_value2'),
            array('compound_key_3', 'new_value3'),
        );
    }

    /**
     * @dataProvider getTestUnsupportedMethod
     */
    public function testUnsupportedMethod($method)
    {
        $this->assertFalse(
            is_callable(array($this->event, $method)),
            "The '$method()' $method isn't a valid callable of ParseEvent object."
        );
    }

    public function getTestUnsupportedMethod()
    {
        return array(
            array('key1'),
            array('key2'),
            array('key3'),
            array('getkey1'),
            array('getkey2'),
            array('getkey3'),
            array('Key1'),
            array('Key2'),
            array('Key3'),
            array('getKey1'),
            array('getKey2'),
            array('getKey3'),
            array('compound_key_1'),
            array('compound_key_2'),
            array('compound_key_3'),
            array('setcompound_key_1'),
            array('setcompound_key_2'),
            array('setcompound_key_3'),
            array('CompoundKey1'),
            array('CompoundKey2'),
            array('CompoundKey3'),
            array('setCompoundKey1'),
            array('setCompoundKey2'),
            array('setCompoundKey3'),
            array('ThisMethodDoesNotExist'),
        );
    }
}