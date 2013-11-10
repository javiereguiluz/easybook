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
use Easybook\Events\BaseEvent;
use Easybook\Tests\TestCase;

class BaseEventTest extends TestCase
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

        $this->event = new BaseEvent($this->app);
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
}