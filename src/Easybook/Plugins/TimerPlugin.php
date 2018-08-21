<?php declare(strict_types=1);

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Easybook\Plugins;

use Easybook\Events\BaseEvent;
use Easybook\Events\EasybookEvents as Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * It registers the start and the end of the book publication
 * to measure the elapsed time.
 */
final class TimerPlugin implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            Events::PRE_PUBLISH => 'registerPublicationStart',
            Events::POST_PUBLISH => 'registerPublicationEnd',
        ];
    }

    /**
     * It registers the timestamp of the book publication start.
     *
     * @param BaseEvent $event The event object that provides access to the application
     */
    public function registerPublicationStart(BaseEvent $baseEvent): void
    {
        $baseEvent->app['app.timer.start'] = microtime(true);
    }

    /**
     * It registers the timestamp of the book publication end.
     *
     * @param BaseEvent $event The event object that provides access to the application
     */
    public function registerPublicationEnd(BaseEvent $baseEvent): void
    {
        $baseEvent->app['app.timer.finish'] = microtime(true);
    }
}
