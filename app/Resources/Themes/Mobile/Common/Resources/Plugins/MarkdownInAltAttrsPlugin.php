<?php
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Easybook\Events\EasybookEvents as Events;
use Easybook\Events\ParseEvent;

/**
 * This plugin parses Markdown content found inside of alt attributes of images.
 * 
 * Normally, Markdown just ignores any format options inside alt tags. 
 * But we are going to use the content inside alt tags as the tooltip content, 
 * so it needs to be parsed.
 */
class MarkdownInAltAttrsPlugin implements EventSubscriberInterface
{
    static public function getSubscribedEvents()
    {
        return array(
                Events::POST_PARSE => 'onItemPostParse',);
    }

    public function onItemPostParse(ParseEvent $event)
    {
        $item = $event->getItem();

        // process all markdown in alt attributes
        $item['content'] = preg_replace_callback('/alt="(.*)"/Ums',
                function ($matches) use ($event)
                {
                    $parsed = $event->app->get('parser')->parse($matches[1]);
                    $newAlt = trim($parsed['content']);
                    
                    // remove surrounding quotes
                    $newAlt = preg_replace('/&quot;(.*)&quot;/Ums', '$1', $newAlt);
                    
                    // remove surrounding paragraph
                    $newAlt = preg_replace('/<p>(.*)<\/p>/Ums', '$1', $newAlt);
                    
                    // final trim to remove final new line char
                    $newAlt = trim($newAlt);

                    return sprintf('alt="%s"', $newAlt);
                }, $item['content']);

        $event->setItem($item);
    }
}

