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

use Easybook\Events\BaseEvent;

class ParseEvent extends BaseEvent
{
    public function getItem()
    {
        return $this->app['publishing.parsing_item'];
    }

    public function setItem($item)
    {
        $this->app->set('publishing.parsing_item', $item);
    }

    /*
     * Magic getters and setters for any 'item' value
     */
    public function __call($method, $arguments)
    {
        if ('get' == substr($method, 0, 3)) {
            $id = lcfirst(substr($method, 3));

            return $this->getItemProperty($id);
        }
        elseif ('set' == substr($method, 0, 3)) {
            $id = lcfirst(substr($method, 3));
            $value = $arguments[0];

            $this->setItemProperty($id, $value);
        }
        else {
            throw new \BadMethodCallException(sprintf(
                'Undefined "%s" method (the method name must start with either "get" or "set"',
                $method
            ));
        }
    }

    private function getItemProperty($id)
    {
        return $this->app['publishing.parsing_item'][$id];
    }

    private function setItemProperty($id, $value)
    {
        $item = $this->app['publishing.parsing_item'];
        $item[$id] = $value;

        $this->app->set('publishing.parsing_item', $item);
    }
}