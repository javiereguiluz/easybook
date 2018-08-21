<?php declare(strict_types=1);

namespace Easybook\Plugins;

use Easybook\Events\EasybookEvents as Events;
use Easybook\Events\ParseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * It performs some operations on the book images, such as
 * fixing their URLs and adding labels to them.
 */
final class ImagePlugin implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            Events::POST_PARSE => [['fixImageUris', -500], ['decorateAndLabelImages', -500]],
        ];
    }

    /**
     * It fixes all the image URIs by prefixing the base_dir configured in the book
     * edition. This is mostly used for 'html' and ' html_chunked' editions when
     * the book is published as a website.
     *
     * @see 'images_base_dir' option in easybook-doc-en/05-publishing-html-books.md
     */
    public function fixImageUris(ParseEvent $parseEvent): void
    {
        $item = $parseEvent->getItem();
        $baseDir = $parseEvent->app->edition('images_base_dir');

        $item['content'] = preg_replace_callback(
            '/<img src="(.*)"(.*) \/>/U',
            function ($matches) use ($baseDir) {
                $uri = $matches[1];
                $uri = $baseDir . $uri;

                return sprintf('<img src="%s"%s />', $uri, $matches[2]);
            },
            $item['content']
        );

        $parseEvent->setItem($item);
    }

    /**
     * It decorates each image with a template and, if the edition configures it,
     * with the appropriate auto-numbered label.
     */
    public function decorateAndLabelImages(ParseEvent $parseEvent): void
    {
        $item = $parseEvent->getItem();

        $addImageLabels = in_array('figure', $parseEvent->app->edition('labels') ?: [], true);
        $parentItemNumber = $item['config']['number'];
        $listOfImages = [];
        $counter = 0;

        $item['content'] = preg_replace_callback(
            // the regexp matches:
            //   1. <img (...optional...) alt="..." (...optional...) />
            //
            //   2. <div class="(left OR center OR right)">
            //        <img (...optional...) alt="..." (...optional...) />
            //      </div>
            '/(<p>)?(<div class="(?<align>.*)">)?(?<content><img .*alt="(?<title>[^"]*)".*\/>)(<\/div>)?(<\/p>)?/',
            function ($matches) use ($parseEvent, $addImageLabels, $parentItemNumber, &$listOfImages, &$counter) {
                // prepare figure parameters for the template and the label
                $parameters = [
                    'item' => [
                        'align' => $matches['align'],
                        'caption' => $matches['title'],
                        'content' => $matches['content'],
                        'label' => '',
                        'number' => null,
                        'slug' => '',
                    ],
                    'element' => [
                        'number' => $parentItemNumber,
                    ],
                ];

                // '*' in title means this is a decorative image instead of
                // a book figure or illustration
                if ($matches['title'] !== '*') {
                    $counter++;
                    $parameters['item']['number'] = $counter;
                    $parameters['item']['slug'] = $parseEvent->app->slugify(
                        'Figure ' . $parentItemNumber . '-' . $counter
                    );

                    // the publishing edition wants to label figures/images
                    if ($addImageLabels) {
                        $label = $parseEvent->app->getLabel('figure', $parameters);
                        $parameters['item']['label'] = $label;
                    }

                    // add image details to the list-of-images
                    $listOfImages[] = $parameters;
                }

                return $parseEvent->app->render('figure.twig', $parameters);
            },
            $item['content']
        );

        if (count($listOfImages) > 0) {
            $parseEvent->app->append('publishing.list.images', $listOfImages);
        }

        $parseEvent->setItem($item);
    }
}
