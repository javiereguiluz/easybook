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
     * @var string|null
     */
    private $editionFormat;

    public function __construct(Item $item, ?string $editionFormat = null)
    {
        $this->item = $item;
        $this->editionFormat = $editionFormat;
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
