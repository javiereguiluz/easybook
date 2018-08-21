<?php declare(strict_types=1);

namespace Easybook\Events;

use Symfony\Component\EventDispatcher\Event;

/**
 * The object passed to most of the events. It provides access for
 * both the application object and the specific item being published.
 */
abstract class AbstractEvent extends Event
{
    /**
     * Getter for the specific item that is being published at
     * the moment (e.g. a book chapter).
     *
     * @return array The item data
     */
    public function getItem(): array
    {
        return $this->app['publishing.active_item'];
    }

    /**
     * Setter to modify the item that is being published at
     * the moment (e.g. a book chapter).
     *
     * @param array $item The item that replaces the old item data
     */
    public function setItem(array $item): void
    {
        $this->app['publishing.active_item'] = $item;
    }
}
