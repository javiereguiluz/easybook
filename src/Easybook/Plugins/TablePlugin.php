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

class TablePlugin implements EventSubscriberInterface
{
    static public function getSubscribedEvents()
    {
        return array(
            Events::POST_PARSE => array('onItemPostParse', -500),
        );
    }

    public function onItemPostParse(ParseEvent $event)
    {
        // decorate each table with a template (and add labels if needed)
        $item = $event->getItem();
        $listOfTables = array();
        $elementNumber = $item['config']['number'];
        $counter = 0;
        $item['content'] = preg_replace_callback(
            '/(?<content><table.*\n<\/table>)/Ums',
            function($matches) use ($event, $elementNumber, &$listOfTables, &$counter) {
                // prepare item parameters for template and label
                $counter++;
                $parameters = array(
                    'item' => array(
                        'caption' => '',
                        'content' => $matches['content'],
                        'label'   => '',
                        'number'  => $counter,
                        'slug'    => $event->app->get('slugger')->slugify('Table '.$elementNumber.'-'.$counter)
                    ),
                    'element' => array(
                        'number' => $elementNumber
                    )
                );

                // the publishing edition wants to label tables
                if (in_array('table', $event->app->edition('labels'))) {
                    $label = $event->app->getLabel('table', $parameters);
                    $parameters['item']['label'] = $label;
                }

                // add table datails to list-of-images
                $listOfTables[] = $parameters;

                return $event->app->render('table.twig', $parameters);
            },
            $item['content']
        );

        $event->app->append('publishing.list.tables', $listOfTables);

        $event->setItem($item);
    }
}