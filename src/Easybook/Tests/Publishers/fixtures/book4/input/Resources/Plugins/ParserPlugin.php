<?php
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Easybook\Events\EasybookEvents as Events;
use Easybook\Events\ParseEvent;

class ParserPlugin implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            Events::PRE_PARSE  => 'onItemPreParse',
            Events::POST_PARSE => 'onItemPostParse',
        );
    }

    public function onItemPreParse(ParseEvent $event)
    {
        $txt = str_replace(
            '**easybook**',
            '*eAsYbOoK*',
            $event->getItemProperty('original')
        );

        $event->setItemProperty('original', $txt);
    }

    public function onItemPostParse(ParseEvent $event)
    {
        $html = str_replace(
            '<em>eAsYbOoK</em>',
            '<strong class="branding">easybook</strong>',
            $event->getItemProperty('content')
        );

        $event->setItemProperty('content', $html);
    }
}
