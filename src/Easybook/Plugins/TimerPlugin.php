<?php

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Easybook\Plugins;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Easybook\Events\EasybookEvents as Events;
use Easybook\Events\BaseEvent;

class TimerPlugin implements EventSubscriberInterface
{
    static public function getSubscribedEvents()
    {
        return array(
            Events::PRE_PUBLISH  => 'onStart',
            Events::POST_PUBLISH => 'onFinish',
        );
    }

    public function onStart(BaseEvent $event)
    {
        $event->app->set('app.timer.start', microtime(true));
    }

    public function onFinish(BaseEvent $event)
    {
        $event->app->set('app.timer.finish', microtime(true));
    }
}