<?php declare(strict_types=1);

use Easybook\Events\EasybookEvents as Events;
use Easybook\Events\ParseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ParserPlugin implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            Events::PRE_PARSE => 'onItemPreParse',
            Events::POST_PARSE => 'onItemPostParse',
        ];
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
