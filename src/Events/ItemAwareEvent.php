<?php declare(strict_types=1);

namespace Easybook\Events;

use Easybook\Book\Edition;
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
     * @var Edition
     */
    private $edition;

    public function __construct(Item $item, Edition $edition)
    {
        $this->item = $item;
        $this->edition = $edition;
    }

    public function getItem(): Item
    {
        return $this->item;
    }

    public function getEdition(): Edition
    {
        return $this->edition;
    }
}
