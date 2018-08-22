<?php declare(strict_types=1);

use Easybook\Events\EasybookEvents;
use Easybook\Events\ParseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ParserPluginEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): Iterator
    {
        yield EasybookEvents::PRE_PARSE => 'onItemPreParse';
        yield EasybookEvents::POST_PARSE => 'onItemPostParse';
    }

    public function onItemPreParse(ParseEvent $parseEvent): void
    {
        $txt = str_replace('**easybook**', '*eAsYbOoK*', $parseEvent->getItemProperty('original'));

        $parseEvent->setItemProperty('original', $txt);
    }

    public function onItemPostParse(ParseEvent $parseEvent): void
    {
        $html = str_replace(
            '<em>eAsYbOoK</em>',
            '<strong class="branding">easybook</strong>',
            $parseEvent->getItemProperty('content')
        );

        $parseEvent->setItemProperty('content', $html);
    }
}
