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
        $this->app->set('publishing.active_item', $item);

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
    public function testGetMethod($getMethod, $expectedResult)
    {
        $this->assertEquals($expectedResult, $this->event->{$getMethod}());
    }

    public function getTestGetMethodData()
    {
        return array(
            array('getKey1', 'value1'),
            array('getKey2', 'value2'),
            array('getKey3', 'value3'),
            array('getCompound_key_1', 'value1'),
            array('getCompound_key_2', 'value2'),
            array('getCompound_key_3', 'value3'),
        );
    }

    /**
     * @dataProvider getTestSetMethodData
     */
    public function testSetMethod($setMethod, $expectedResult)
    {
        $this->event->{$setMethod}($expectedResult);

        $getMethod = str_replace('set', 'get', $setMethod);
        $this->assertEquals($expectedResult, $this->event->{$getMethod}());
    }

    public function getTestSetMethodData()
    {
        return array(
            array('setKey1', 'new_value1'),
            array('setKey2', 'new_value2'),
            array('setKey3', 'new_value3'),
            array('setCompound_key_1', 'new_value1'),
            array('setCompound_key_2', 'new_value2'),
            array('setCompound_key_3', 'new_value3'),
        );
    }

    /**
     * @dataProvider getTestUnsupportedMethod
     */
    public function testUnsupportedMethod($method)
    {
        try {
            $value = $this->event->{$method}();
        } catch (\BadMethodCallException $e) {
            $this->assertInstanceOf('BadMethodCallException', $e);
            $this->assertContains('(the method name must start with either "get" or "set")', $e->getMessage());
        }
    }

    public function getTestUnsupportedMethod()
    {
        return array(
            array('key1'),
            array('key2'),
            array('key3'),
            array('Key1'),
            array('Key2'),
            array('Key3'),
            array('compound_key_1'),
            array('compound_key_2'),
            array('compound_key_3'),
            array('CompoundKey1'),
            array('CompoundKey2'),
            array('CompoundKey3'),
            array('ThisMethodDoesNotExist'),
        );
    }
}