<?php declare(strict_types=1);

namespace Easybook\Tests\Publishers;

use Iterator;
use Easybook\Configuration\CurrentItemProvider;
use Easybook\Tests\AbstractContainerAwareTestCase;

final class ParseEventTest extends AbstractContainerAwareTestCase
{
    /**
     * @var mixed[]
     */
    private $item = [];

    /**
     * @var CurrentItemProvider
     */
    private $currentItemProvider;

    protected function setUp(): void
    {
        $this->currentItemProvider = $this->container->get(CurrentItemProvider::class);

        $this->item = $item = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
            'compound_key_1' => 'value1',
            'compound_key_2' => 'value2',
            'compound_key_3' => 'value3',
        ];

        $this->currentItemProvider->setItem($item);
    }

    public function testGetItem(): void
    {
        $this->assertSame($this->item, $this->currentItemProvider->getItem());
    }

    public function testSetItem(): void
    {
        $newItem = array_reverse($this->item);

        $this->currentItemProvider->setItem($newItem);

        $this->assertSame($newItem, $this->currentItemProvider->getItem());
    }

    /**
     * @dataProvider getTestGetMethodData()
     */
    public function testGetMethod($key, $expectedValue): void
    {
        $this->assertSame($expectedValue, $this->currentItemProvider->getItemProperty($key));
    }

    public function getTestGetMethodData(): Iterator
    {
        yield ['key1', 'value1'];
        yield ['key2', 'value2'];
        yield ['key3', 'value3'];
        yield ['compound_key_1', 'value1'];
        yield ['compound_key_2', 'value2'];
        yield ['compound_key_3', 'value3'];
    }

    /**
     * @dataProvider getTestSetMethodData
     */
    public function testSetMethod($key, $expectedValue): void
    {
        $this->currentItemProvider->setItemProperty($key, $expectedValue);

        $this->assertSame($expectedValue, $this->currentItemProvider->getItemProperty($key));
    }

    public function getTestSetMethodData()
    {
        return [
            ['key1', 'new_value1'],
            ['key2', 'new_value2'],
            ['key3', 'new_value3'],
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
