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
use Easybook\Events\ParseEvent;

class LinkPlugin implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            // priority must be lower than ParserPlugin POST_PARSE priority (-1000)
            Events::POST_PARSE   => array('onItemPostParse', -1500),
            Events::PRE_DECORATE => array('onItemPreDecorate', -500),
        );
    }

    /*
     * Creates a lookup table for internal links (only used in html_chunked
     * and epub type editions).
     *
     * This table is saved in `publishing.links` and has the following structure:
     * array(
     *     '#my-internal-link-id'    => 'chapter-2.html#my-internal-link-id',
     *     '#my-other-internal-link' => 'chapter-3.html#my-other-internal-link',
     *     '#another-internal-link'  => 'chapter-7.html#another-internal-link',
     * )
     */
    public function onItemPostParse(ParseEvent $event)
    {
        $format = $event->app->edition('format');
        if (!in_array($format, array('html_chunked', 'epub', 'epub2'))) {
            return;
        }

        $item = $event->getItem();
        $links = $event->app->get('publishing.links');

        foreach ($item['toc'] as $entry) {
            if ('html_chunked' == $format) {
                $itemSlug = $event->app->get('slugger')->slugify(trim($item['label']), array('unique' => false));
            } elseif (in_array($format, array('epub', 'epub2'))) {
                $itemSlug = array_key_exists('number', $item['config'])
                    ? $item['config']['element'].'-'.$item['config']['number']
                    : $item['config']['element'];
            }

            $relativeUrl = '#'.$entry['slug'];
            $absoluteUrl = $itemSlug.'.html'.$relativeUrl;

            $links[$relativeUrl] = $absoluteUrl;
        }

        $event->app->set('publishing.links', $links);
    }

    public function onItemPreDecorate(BaseEvent $event)
    {
        $item = $event->getItem();

        $item['content'] = preg_replace_callback(
            '/<a href="(#.*)"(.*)<\/a>/Us',
            function($matches) use ($event) {
                $format = $event->app->edition('format');
                if (in_array($format, array('html_chunked', 'epub', 'epub2'))) {
                    $links = $event->app->get('publishing.links');

                    return sprintf(
                        '<a class="link:internal" href="./%s"%s</a>',
                        $links[$matches[1]], $matches[2]
                    );
                } else {
                    return sprintf(
                        '<a class="link:internal" href="%s"%s</a>',
                        $matches[1], $matches[2]
                    );
                }
            },
            $item['content']
        );

        $event->setItem($item);
    }
}
