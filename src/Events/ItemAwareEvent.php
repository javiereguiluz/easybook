<?php declare(strict_types=1);

namespace Easybook\Events;

use Easybook\Book\Item;
use Symfony\Component\EventDispatcher\Event;

/**
 * The object passed to the events related to the parsing of contents.
 * It provides access for the application object, the specific item
 * being published and to any of the item's properties.
 */
final class ItemAwareEvent extends Event
{
    /**
     * @var Item
     */
    private $item;

    /**
     * @var null|string
     */
    private $editionFormat;

    // @todo should be object to prevent set/get games just to keep reference

    public function __construct(Item $item, ?string $editionFormat = null)
    {
        $this->item = $item;
        $this->editionFormat = $editionFormat;
    }

    /**
     * @return mixed The value of the requested property
     */
    public function getItemProperty(string $key)
    {
        return $this->item[$key];
    }

    /**
     * @param mixed $value
     */
    public function changeItemProperty(string $key, $value): void
    {
        $this->item[$key] = $value;
    }

    public function getItem(): Item
    {
        return $this->item;
    }

    public function getEditionFormat(): ?string
    {
        return $this->editionFormat;
    }
}
