<?php declare(strict_types=1);

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
     */
    public function registerPublicationStart(BaseEvent $baseEvent): void
    {
        $baseEvent->app['app.timer.start'] = microtime(true);
    }

    /**
     * It registers the timestamp of the book publication end.
     */
    public function registerPublicationEnd(BaseEvent $baseEvent): void
    {
        $baseEvent->app['app.timer.finish'] = microtime(true);
    }
}
