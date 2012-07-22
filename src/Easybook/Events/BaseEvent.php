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

use Symfony\Component\EventDispatcher\Event;

class BaseEvent extends Event
{
    public $app;

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function getItem()
    {
        return $this->app['publishing.active_item'];
    }

    public function setItem($item)
    {
        $this->app->set('publishing.active_item', $item);
    }
}
