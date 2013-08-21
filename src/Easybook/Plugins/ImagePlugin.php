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
 * It performs some operations on the book images, such as
 * fixing their URLs and adding labels to them.
 */
class ImagePlugin implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            Events::POST_PARSE => array(
                array('fixImageUris', -500),
                array('decorateAndLabelImages', -500),
            ),
        );
    }

    /**
     * It fixes all the image URIs by prefixing the base_dir configured in the book
     * edition. This is mostly used for 'html' and ' html_chunked' editions when
     * the book is published as a website.
     *
     * @see 'images_base_dir' option in easybook-doc-en/05-publishing-html-books.md
     *
     * @param ParseEvent $event The object that contains the item being processed
     */
    public function fixImageUris(ParseEvent $event)
    {
        $item = $event->getItem();
        $baseDir = $event->app->edition('images_base_dir');

        $item['content'] = preg_replace_callback(
            '/<img src="(.*)"(.*) \/>/U',
            function ($matches) use ($baseDir) {
                $uri = $matches[1];
                $uri = $baseDir.$uri;

                return sprintf('<img src="%s"%s />', $uri, $matches[2]);
            },
            $item['content']
        );

        $event->setItem($item);
    }

    /**
     * It decorates each image with a template and, if the edition configures it,
     * with the appropriate auto-numbered label.
     *
     * @param ParseEvent $event The object that contains the item being processed
     */
    public function decorateAndLabelImages(ParseEvent $event)
    {
        $item = $event->getItem();

        $addImageLabels   = in_array('figure', $event->app->edition('labels') ?: array());
        $parentItemNumber = $item['config']['number'];
        $listOfImages     = array();
        $counter          = 0;

        $item['content'] = preg_replace_callback(
            // the regexp matches:
            //   1. <img (...optional...) alt="..." (...optional...) />
            //
            //   2. <div class="(left OR center OR right)">
            //        <img (...optional...) alt="..." (...optional...) />
            //      </div>
            '/(<p>)?(<div class="(?<align>.*)">)?(?<content><img .*alt="(?<title>[^"]*)".*\/>)(<\/div>)?(<\/p>)?/',
            function($matches) use ($event, $addImageLabels, $parentItemNumber, &$listOfImages, &$counter) {
                // prepare figure parameters for the template and the label
                $parameters = array(
                    'item' => array(
                        'align'   => $matches['align'],
                        'caption' => $matches['title'],
                        'content' => $matches['content'],
                        'label'   => '',
                        'number'  => null,
                        'slug'    => ''
                    ),
                    'element' => array(
                        'number' => $parentItemNumber
                    )
                );

                // '*' in title means this is a decorative image instead of
                // a book figure or illustration
                if ('*' != $matches['title']) {
                    $counter++;
                    $parameters['item']['number'] = $counter;
                    $parameters['item']['slug']   = $event->app->slugify('Figure '.$parentItemNumber.'-'.$counter);

                    // the publishing edition wants to label figures/images
                    if ($addImageLabels) {
                        $label = $event->app->getLabel('figure', $parameters);
                        $parameters['item']['label'] = $label;
                    }

                    // add image details to the list-of-images
                    $listOfImages[] = $parameters;
                }

                return $event->app->render('figure.twig', $parameters);
            },
            $item['content']
        );

        if (count($listOfImages) > 0) {
            $event->app->append('publishing.list.images', $listOfImages);
        }

        $event->setItem($item);
    }
}
