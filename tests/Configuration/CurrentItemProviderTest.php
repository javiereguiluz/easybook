<?php declare(strict_types=1);

namespace Easybook\Tests\Configuration;

use Iterator;
use Easybook\Configuration\CurrentItemProvider;
use Easybook\Tests\AbstractContainerAwareTestCase;

final class CurrentItemProviderTest extends AbstractContainerAwareTestCase
{
    /**
     * @var mixed[]
     */
    private $item = [
        'key1' => 'value1',
        'key2' => 'value2',
        'key3' => 'value3',
        'compound_key_1' => 'value1',
        'compound_key_2' => 'value2',
        'compound_key_3' => 'value3',
    ];

    /**
     * @var CurrentItemProvider
     */
    private $currentItemProvider;

    protected function setUp(): void
    {
        $this->currentItemProvider = $this->container->get(CurrentItemProvider::class);
    }

    public function testGetAndSetItem(): void
    {
        $this->currentItemProvider->setItem($this->item);
        $this->assertSame($this->item, $this->currentItemProvider->getItem());

        $newItem = array_reverse($this->item);
        $this->currentItemProvider->setItem($newItem);
        $this->assertSame($newItem, $this->currentItemProvider->getItem());
    }

    /**
     * @dataProvider getTestGetMethodData()
     */
    public function testGetMethod(string $key, string $expectedValue): void
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
     * @dataProvider getTestSetMethodData()
     */
    public function testSetMethod(string $key, string $expectedValue): void
    {
        $this->currentItemProvider->setItemProperty($key, $expectedValue);

        $this->assertSame($expectedValue, $this->currentItemProvider->getItemProperty($key));
    }

    public function getTestSetMethodData(): Iterator
    {
        yield ['key1', 'new_value1'];
        yield ['key2', 'new_value2'];
        yield ['key3', 'new_value3'];
        yield ['compound_key_1', 'new_value1'];
        yield ['compound_key_2', 'new_value2'];
        yield ['compound_key_3', 'new_value3'];
    }
}
