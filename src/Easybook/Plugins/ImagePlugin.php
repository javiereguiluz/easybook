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

class ImagePlugin implements EventSubscriberInterface
{
    static public function getSubscribedEvents()
    {
        return array(
            Events::POST_PARSE => array('onItemPostParse', -500),
        );
    }

    public function onItemPostParse(ParseEvent $event)
    {
        // fix image URLs
        $item = $event->getItem();
        $item['content'] = preg_replace_callback(
            '/<img src="(.*)"(.*) \/>/U',
            function($matches) {
                $uri = $matches[1];
                if ('images/' != substr($uri, 0, 7)) {
                    $uri = 'images/'.$uri;
                }

                return sprintf('<img src="%s"%s />', $uri, $matches[2]);
            },
            $item['content']
        );
        $event->setItem($item);

        // decorate each image with a template (and add labels if needed)
        $item = $event->getItem();
        $listOfImages = array();
        $elementNumber = $item['config']['number'];
        $counter = 0;
        $item['content'] = preg_replace_callback(
            '/(?<content><img .*alt="(?<title>.*)".*\/>)/U',
            function($matches) use ($event, $elementNumber, &$listOfImages, &$counter) {
                // prepare item parameters for template and label
                $counter++;
                $parameters = array(
                    'item' => array(
                        'caption' => $matches['title'],
                        'content' => $matches['content'],
                        'label'   => '',
                        'number'  => $counter,
                        'slug'    => $event->app->get('slugger')->slugify('Figure '.$elementNumber.'-'.$counter)
                    ),
                    'element' => array(
                        'number' => $elementNumber
                    )
                );

                // the publishing edition wants to label figures/images
                if (in_array('figure', $event->app->edition('labels'))) {
                    $label = $event->app->getLabel('figure', $parameters);
                    $parameters['item']['label'] = $label;
                }

                // add image datails to list-of-images
                $listOfImages[] = $parameters;

                return $event->app->render('figure.twig', $parameters);
            },
            $item['content']
        );

        $event->app->append('publishing.list.images', $listOfImages);

        $event->setItem($item);
    }
}