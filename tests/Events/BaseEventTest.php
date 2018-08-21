<?php declare(strict_types=1);

namespace Easybook\Tests\Publishers;

use Easybook\Events\AbstractEvent;
use Easybook\Tests\AbstractContainerAwareTestCase;

final class BaseEventTest extends AbstractContainerAwareTestCase
{
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

        $this->app['publishing.active_item'] = $item;

        $this->event = new AbstractEvent($this->app);
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
}
