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

    /*
     * Magic getters and setters for any 'item' value
     *
     * @return mixed The value of the given item property
     *
     *  @throws \BadMethodCallException If the requested method is not a getter or a setter
     */
    public function __call($method, $arguments)
    {
        if ('get' == substr($method, 0, 3)) {
            $id = lcfirst(substr($method, 3));

            return $this->getItemProperty($id);
        } elseif ('set' == substr($method, 0, 3)) {
            $id = lcfirst(substr($method, 3));
            $value = $arguments[0];

            $this->setItemProperty($id, $value);
        } else {
            throw new \BadMethodCallException(sprintf(
                'Undefined "%s" method (the method name must start with either "get" or "set")',
                $method
            ));
        }
    }

    /**
     * Getter for any of the properties of the item being published
     *
     * @return mixed The value of the requested property
     */
    private function getItemProperty($id)
    {
        return $this->app['publishing.active_item'][$id];
    }

    /**
     * Setter for any of the properties of the item being published.
     *
     * @param string $id    The id of the property to modify
     * @param mixed  $value The new value of the property
     */
    private function setItemProperty($id, $value)
    {
        $item = $this->app['publishing.active_item'];
        $item[$id] = $value;

        $this->app['publishing.active_item'] = $item;
    }
}
