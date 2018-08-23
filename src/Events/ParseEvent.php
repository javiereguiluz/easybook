<?php declare(strict_types=1);

namespace Easybook\Events;

use Symfony\Component\EventDispatcher\Event;

/**
 * The object passed to the events related to the parsing of contents.
 * It provides access for the application object, the specific item
 * being published and to any of the item's properties.
 */
final class ParseEvent extends Event
{
    /**
     * @var mixed[]
     */
    private $item = [];

    // @todo should be object to prevent set/get games just to keep reference

    /**
     * @param mixed[] $item
     */
    public function __construct(array $item)
    {
        $this->item = $item;
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

    /**
     * @return mixed[]
     */
    public function getItem(): array
    {
        return $this->item;
    }
}
