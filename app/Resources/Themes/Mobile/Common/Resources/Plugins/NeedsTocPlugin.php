<?php
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Easybook\Events\EasybookEvents as Events;
use Easybook\Events\BaseEvent;

/**
 * This plugin adds a TOC if the book doesn't have one.
 * 
 */
class NeedsTocPlugin implements EventSubscriberInterface
{
    static public function getSubscribedEvents()
    {
        return array(
                Events::PRE_PUBLISH => 'onBookPrePublish');
    }
  
    public function onBookPrePublish(BaseEvent $event)
    {
        $app = $event->app;

        // look for toc in book
        $toc = false;
        foreach ($app->book('contents') as $item) {
            if ('toc' == $item['element']) {
                $toc = true;
                break;
            }
        }

        // if no toc present, add forced toc at the end
        if (!$toc) {
            $item = array(
                    'element' => 'toc');
            
            $book = $app->book('contents');
            $book[] = $item;
            
            $app->book('contents', $book);
        }
    }
}

