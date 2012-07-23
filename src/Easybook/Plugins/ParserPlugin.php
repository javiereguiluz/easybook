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
use Easybook\Events\ParseEvent;

class ParserPlugin implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            Events::POST_PARSE => array('onItemPostParse', -1000),
        );
    }

    public function onItemPostParse(ParseEvent $event)
    {
        // replace <br> by <br/> (it causes problems for epub books)
        $item = $event->getItem();
        $item['content'] = str_replace('<br>', '<br/>', $item['content']);
        $event->setItem($item);

        // strip title from the parsed content
        $item = $event->getItem();
        if (count($item['toc']) > 0) {
            $heading = $item['toc'][0];

            // only <h1> headings can be the title of the content
            if (1 == $heading['level']) {
                // the <h1> heading must be the first line to consider it a title
                $item['content'] = preg_replace('{
                        ^<h1.*<\/h1>\n+(.*)
                    }x',
                    '$1',
                    $item['content']
                );

                $item['slug']  = $heading['slug'];
                $item['title'] = $heading['title'];
            }
        }

        $event->setItem($item);

        // add labels
        $item = $event->getItem();
        if (count($item['toc']) > 0) {
            // prepare labels
            $counters = array(
                1 => $item['config']['number'],
                2 => 0,
                3 => 0,
                4 => 0,
                5 => 0,
                6 => 0
            );
            foreach ($item['toc'] as $key => $entry) {
                // edition config allows labels for this element type ('labels' option)
                if (in_array($item['config']['element'], $event->app->edition('labels') ?: array())) {
                    $level = $entry['level'];
                    if ($level > 1) {
                        $counters[$level]++;
                    }

                    // Reset the counters for the higher heading levels
                    for ($i = $level+1; $i <= 6; $i++) {
                        $counters[$i] = 0;
                    }

                    $parameters = array_merge($item['config'], array(
                        'counters' => $counters,
                        'level'    => $level
                    ));
                    $label = $event->app->getLabel($item['config']['element'], array(
                        'item' => $parameters
                    ));
                }
                // edition config doesn't allow labels for this element type
                else {
                    $label = "";
                }

                $entry['label'] = $label;
                $item['toc'][$key] = $entry;
            }

            // the label of the item matches the label of the first toc element
            $item['label'] = $item['toc'][0]['label'];

            $event->setItem($item);

            // add labels to content
            $item = $event->getItem();
            foreach ($item['toc'] as $i => $entry) {
                // the parsed title can be different from the toc entry title
                // that's the case for the titles with markup code inside (* ` ** etc.)
                // thus, the replacement must be done based on a fuzzy title that
                // doesn't include the title text
                $fuzzyTitle = "/<h".$entry['level']." id=\"".$entry['slug']."\">.*<\/h".$entry['level'].">\n\n/";

                $labeledTitle = sprintf("<h%s id=\"%s\">%s%s</h%s>\n\n",
                    $entry['level'],
                    $entry['slug'],
                    $entry['label'],
                    '' != $entry['label'] ? ' '.$entry['title'] : $entry['title'],
                    $entry['level']
                );

                $item['content'] = preg_replace($fuzzyTitle, $labeledTitle, $item['content']);
            }

            $event->setItem($item);
        }

        // ensure that the item has a title (using the default title if necessary)
        $item = $event->getItem();
        if ('' == $item['title']) {
            $item['title'] = $event->app->getTitle($item['config']['element']);
            $item['slug']  = $event->app->get('slugger')->slugify($item['title']);

            $event->setItem($item);
        }
    }
}
