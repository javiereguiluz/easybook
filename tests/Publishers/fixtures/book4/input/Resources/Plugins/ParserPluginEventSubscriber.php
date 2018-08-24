<?php declare(strict_types=1);

use Easybook\Events\EasybookEvents;
use Easybook\Events\ItemAwareEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ParserPluginEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): Iterator
    {
        yield EasybookEvents::PRE_PARSE => 'onItemPreParse';
        yield EasybookEvents::POST_PARSE => 'onItemPostParse';
    }

    public function onItemPreParse(ItemAwareEvent $itemAwareEvent): void
    {
        $txt = str_replace('**easybook**', '*eAsYbOoK*', $itemAwareEvent->getItemProperty('original'));

        $itemAwareEvent->changeItemProperty('original', $txt);
    }

    public function onItemPostParse(ItemAwareEvent $itemAwareEvent): void
    {
        $html = str_replace(
            '<em>eAsYbOoK</em>',
            '<strong class="branding">easybook</strong>',
            $itemAwareEvent->getItemProperty('content')
        );

        $itemAwareEvent->changeItemProperty('content', $html);
    }
}
