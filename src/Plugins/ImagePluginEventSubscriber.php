<?php declare(strict_types=1);

namespace Easybook\Plugins;

use Easybook\Book\Provider\ImagesProvider;
use Easybook\Events\EasybookEvents;
use Easybook\Events\ItemAwareEvent;
use Easybook\Templating\Renderer;
use Easybook\Util\Slugger;
use Iterator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * It performs some operations on the book images, such as
 * fixing their URLs and adding labels to them.
 */
final class ImagePluginEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var Renderer
     */
    private $renderer;

    /**
     * @var int
     */
    private $counter = 0;

    /**
     * @var Slugger
     */
    private $slugger;

    /**
     * @var ImagesProvider
     */
    private $imagesProvider;

    public function __construct(Renderer $renderer, Slugger $slugger, ImagesProvider $imagesProvider)
    {
        $this->renderer = $renderer;
        $this->slugger = $slugger;
        $this->imagesProvider = $imagesProvider;
    }

    public static function getSubscribedEvents(): Iterator
    {
        yield EasybookEvents::POST_PARSE => [['fixImageUris', -500], ['decorateAndLabelImages', -500]];
    }

    /**
     * It fixes all the image URIs by prefixing the base_dir configured in the book
     * edition.
     *
     * @see 'images_base_dir' option in easybook-doc-en/05-publishing-html-books.md
     */
    public function fixImageUris(ItemAwareEvent $itemAwareEvent): void
    {
        $item = $itemAwareEvent->getItem();
        $baseDir = $itemAwareEvent->app->edition('images_base_dir');

        $item->changeContent(preg_replace_callback(
            '/<img src="(.*)"(.*) \/>/U',
            function ($matches) use ($baseDir) {
                $uri = $matches[1];
                $uri = $baseDir . $uri;

                return sprintf('<img src="%s"%s />', $uri, $matches[2]);
            },
            $item->getContent()
        ));
    }

    /**
     * It decorates each image with a template and, if the edition configures it,
     * with the appropriate auto-numbered label.
     */
    public function decorateAndLabelImages(ItemAwareEvent $itemAwareEvent): void
    {
        $item = $itemAwareEvent->getItem();

        $addImageLabels = in_array('figure', $itemAwareEvent->app->edition('labels') ?: [], true);
        $parentItemNumber = $item->getConfigNumber();

        $this->counter = 0;

        $item->changeContent(preg_replace_callback(
            // the regexp matches:
            //   1. <img (...optional...) alt="..." (...optional...) />
            //
            //   2. <div class="(left OR center OR right)">
            //        <img (...optional...) alt="..." (...optional...) />
            //      </div>
            '/(<p>)?(<div class="(?<align>.*)">)?(?<content><img .*alt="(?<title>[^"]*)".*\/>)(<\/div>)?(<\/p>)?/',
            function ($matches) use ($itemAwareEvent, $addImageLabels, $parentItemNumber) {
                // prepare figure parameters for the template and the label

                // @todo object Image
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
                    $this->counter++;
                    $parameters['item']['number'] = $this->counter;
                    $parameters['item']['slug'] = $this->slugger->slugify(
                        'Figure ' . $parentItemNumber . '-' . $this->counter
                    );

                    // the publishing edition wants to label figures/images
                    if ($addImageLabels) {
                        $label = $itemAwareEvent->app->getLabel('figure', $parameters);
                        $parameters['item']['label'] = $label;
                    }

                    // add image details to the list-of-images
                    $this->imagesProvider->addImage($parameters);
                }

                return $this->renderer->render('figure.twig', $parameters);
            },
            $item->getContent()
        ));
    }
}
