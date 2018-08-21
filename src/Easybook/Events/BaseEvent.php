<?php declare(strict_types=1);

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Easybook\Events;

use Symfony\Component\EventDispatcher\Event;

/**
 * The object passed to most of the events. It provides access for
 * both the application object and the specific item being published.
 */
final class BaseEvent extends Event
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
