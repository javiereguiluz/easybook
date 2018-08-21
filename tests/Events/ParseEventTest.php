<?php declare(strict_types=1);

namespace Easybook\Tests\Publishers;

use Easybook\DependencyInjection\Application;
use Easybook\Events\ParseEvent;
use Easybook\Tests\TestCase;

final class ParseEventTest extends TestCase
{
    public $app;

    public $event;

    public $item;

    protected function setUp(): void
    {
        $this->item = $item = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
            'compound_key_1' => 'value1',
            'compound_key_2' => 'value2',
            'compound_key_3' => 'value3',
        ];

        $this->app = new Application();
        $this->app['publishing.active_item'] = $item;

        $this->event = new ParseEvent($this->app);
    }

    public function testGetItem(): void
    {
        $this->assertSame($this->item, $this->event->getItem());
    }

    public function testSetItem(): void
    {
        $newItem = array_reverse($this->item);
        $this->event->setItem($newItem);

        $this->assertSame($this->item, $this->event->getItem());
    }

    /**
     * @dataProvider getTestGetMethodData
     */
    public function testGetMethod($key, $expectedValue): void
    {
        $this->assertSame($expectedValue, $this->event->getItemProperty($key));
    }

    public function getTestGetMethodData()
    {
        return [
            ['key1',           'value1'],
            ['key2',           'value2'],
            ['key3',           'value3'],
            ['compound_key_1', 'value1'],
            ['compound_key_2', 'value2'],
            ['compound_key_3', 'value3'],
        ];
    }

    /**
     * @dataProvider getTestSetMethodData
     */
    public function testSetMethod($key, $expectedValue): void
    {
        $this->event->setItemProperty($key, $expectedValue);

        $this->assertSame($expectedValue, $this->event->getItemProperty($key));
    }

    public function getTestSetMethodData()
    {
        return [
            ['key1',           'new_value1'],
            ['key2',           'new_value2'],
            ['key3',           'new_value3'],
            ['compound_key_1', 'new_value1'],
            ['compound_key_2', 'new_value2'],
            ['compound_key_3', 'new_value3'],
        ];
    }

    /**
     * @dataProvider getTestUnsupportedMethod
     */
    public function testUnsupportedMethod($method): void
    {
        $this->assertFalse(
            is_callable([$this->event, $method]),
            "The '${method}()' ${method} isn't a valid callable of ParseEvent object."
        );
    }

    public function getTestUnsupportedMethod()
    {
        return [
            ['key1'],
            ['key2'],
            ['key3'],
            ['getkey1'],
            ['getkey2'],
            ['getkey3'],
            ['Key1'],
            ['Key2'],
            ['Key3'],
            ['getKey1'],
            ['getKey2'],
            ['getKey3'],
            ['compound_key_1'],
            ['compound_key_2'],
            ['compound_key_3'],
            ['setcompound_key_1'],
            ['setcompound_key_2'],
            ['setcompound_key_3'],
            ['CompoundKey1'],
            ['CompoundKey2'],
            ['CompoundKey3'],
            ['setCompoundKey1'],
            ['setCompoundKey2'],
            ['setCompoundKey3'],
            ['ThisMethodDoesNotExist'],
        ];
    }
}
