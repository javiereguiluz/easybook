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

/**
 * It performs some operations on the book items after they have been parsed.
 */
class ParserPlugin implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            Events::POST_PARSE => array(
                array('fixHtmlCode', -1000),
                array('setItemTitle', -1000),
                array('addSectionLabels', -1000),
            ),
        );
    }

    /**
     * It fixes the resulting HTML code of the book item. This is necessary
     * to avoid problems with the invalid-HTML-markup-sensitive editions such
     * as the ePub books.
     *
     * @param ParseEvent $event The object that contains the item being processed
     */
    public function fixHtmlCode(ParseEvent $event)
    {
        // replace <br> by <br/> (it causes problems for epub books)
        $item = $event->getItem();
        $item['content'] = str_replace('<br>', '<br/>', $item['content']);
        $event->setItem($item);
    }

    /**
     * Sets the book item title by extracting it from its contents or
     * by using the default title for that book item type.
     *
     * @param ParseEvent $event The object that contains the item being processed
     */
    public function setItemTitle(ParseEvent $event)
    {
        $item = $event->getItem();

        if (count($item['toc']) > 0) {
            $firstItemSection = $item['toc'][0];

            // the title of the content can only be a <h1> heading
            if (1 == $firstItemSection['level']) {
                $item['slug']  = $firstItemSection['slug'];
                $item['title'] = $firstItemSection['title'];

                // strip the title from the parsed content, because the book templates
                // always display the title separately from the rest of the content
                $item['content'] = preg_replace(
                    '/^<h1.*<\/h1>\n+(.*)/x',
                    '$1',
                    $item['content']
                );
            }
        }

        // ensure that every item has a title by using
        // the default title if necessary
        if ('' == $item['title']) {
            $item['title'] = $event->app->getTitle($item['config']['element']);
            $item['slug']  = $event->app->slugify($item['title']);
        }

        $event->setItem($item);
    }

    /**
     * It adds the appropriate auto-numbered labels to the book item sections.
     *
     * @param ParseEvent $event The object that contains the item being processed
     */
    public function addSectionLabels(ParseEvent $event)
    {
        $item = $event->getItem();

        // special book items without a TOC don't need labels
        if (0 == count($item['toc'])) {
            return;
        }

        $counters = array(1 => $item['config']['number'], 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0);
        $addSectionLabels = in_array($item['config']['element'], $event->app->edition('labels') ?: array());

        foreach ($item['toc'] as $key => $entry) {
            if ($addSectionLabels) {
                $level = $entry['level'];

                if ($level > 1) {
                    $counters[$level]++;
                }

                // reset the counters for the higher heading levels
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
            } else {
                $label = '';
            }

            $entry['label'] = $label;
            $item['toc'][$key] = $entry;
        }

        // the label of the item matches the label of its first TOC element
        $item['label'] = $item['toc'][0]['label'];

        // add section labels to the content
        foreach ($item['toc'] as $i => $entry) {
            // the parsed title can be different from the TOC entry title
            // that's the case for the titles with markup code inside (* ` ** etc.)
            // thus, the replacement must be done based on a fuzzy title that
            // doesn't include the title text
            $fuzzyTitle = "/<h".$entry['level']." id=\"".$entry['slug']."\">.*<\/h".$entry['level'].">\n\n/";

            $labeledTitle = sprintf("<h%s id=\"%s\">%s%s</h%s>\n\n",
                $entry['level'],
                $entry['slug'],
                $entry['label'],
                ('' != $entry['label']) ? ' '.$entry['title'] : $entry['title'],
                $entry['level']
            );

            $item['content'] = preg_replace($fuzzyTitle, $labeledTitle, $item['content']);
        }

        $event->setItem($item);
    }
}
