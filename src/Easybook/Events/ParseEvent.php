<?php

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Easybook\Events;

/**
 * The object passed to the events related to the parsing of contents.
 * It provides access for the application object, the specific item
 * being published and to any of the item's properties.
 */
class ParseEvent extends BaseEvent
{
    /**
     * Getter for the specific item that is being published at
     * the moment (e.g. a book chapter)
     *
     * @return array The item data
     */
    public function getItem()
    {
        return $this->app['publishing.active_item'];
    }

    /**
     * Setter to modify the item that is being published at
     * the moment (e.g. a book chapter)
     *
     * @param array $item The item that replaces the old item data
     */
    public function setItem($item)
    {
        $this->app['publishing.active_item'] = $item;
    }

    /**
     * Getter for any of the properties of the item being published
     *
     * @param string $key The name of the requested item property
     *
     * @return mixed The value of the requested property
     */
    public function getItemProperty($key)
    {
        return $this->app['publishing.active_item'][$key];
    }

    /**
     * Setter for any of the properties of the item being published.
     *
     * @param string $key    The name of the property to modify
     * @param mixed  $value  The new value of the property
     */
    public function setItemProperty($key, $value)
    {
        $item = $this->app['publishing.active_item'];
        $item[$key] = $value;

        $this->app['publishing.active_item'] = $item;
    }
}
