<?php declare(strict_types=1);

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Easybook\Plugins;

use Easybook\Events\BaseEvent;
use Easybook\Events\EasybookEvents as Events;
use Easybook\Events\ParseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * It performs some operations on the book links, such as fixing the URLs of
 * the links pointing to internal chapters and sections.
 */
final class LinkPlugin implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            Events::POST_PARSE => ['markInternalLinks'],
            Events::POST_PUBLISH => ['fixInternalLinks', -10],
        ];
    }

    /**
     * It fixes the internal links of the book (the links that point to chapters
     * and sections of the book).
     *
     * The author of the book always uses section ID as link values:
     *   see <a href="#new-content-types">this section</a> for more information
     *
     * In order to work, the ID must be replaced by relative URIs:
     *   see <a href="../chapter3/page-slug.html#new-content-types">this section</a>
     *
     * This replacement cannot be done earlier in the book processing, because
     * books published as websites merge empty sections and the absolute URI
     * cannot be determined until the book has been completely generated.
     *
     * @param BaseEvent $event The event object that provides access to the application
     */
    public function fixInternalLinks(BaseEvent $baseEvent): void
    {
        // Link fixing is only needed for 'html_chunked' editions
        if ($baseEvent->app->edition('format') !== 'html_chunked') {
            return;
        }

        $bookPages = $baseEvent->app['finder']
            ->files()
            ->name('*.html')
            ->in($baseEvent->app['publishing.dir.output']);

        // maps the original internal links (e.g. #new-content-types)
        // with the correct relative URL needed for the website
        // (e.g. chapter-3/advanced-features.html#new-content-types
        $linkMapper = [];

        // look for the ID of every book section
        foreach ($bookPages as $bookPage) {
            $htmlContent = file_get_contents($bookPage->getPathname());

            $matches = [];
            $foundHeadings = preg_match_all(
                '/<h[1-6].*id="(?<id>.*)".*<\/h[1-6]>/U',
                $htmlContent,
                $matches,
                PREG_SET_ORDER
            );

            if ($foundHeadings > 0) {
                foreach ($matches as $match) {
                    $relativeUri = '#' . $match['id'];
                    $absoluteUri = $bookPage->getRelativePathname() . $relativeUri;

                    $linkMapper[$relativeUri] = $absoluteUri;
                }
            }
        }

        // replace the internal relative URIs for the mapped absolute URIs
        foreach ($bookPages as $bookPage) {
            $htmlContent = file_get_contents($bookPage->getPathname());

            // hackish method the detect if this is a first level book page
            // or a page inside a directory
            if (strpos($bookPage->getRelativePathname(), '/') === false) {
                $chunkLevel = 1;
            } else {
                $chunkLevel = 2;
            }

            $htmlContent = preg_replace_callback(
                '/<a href="(?<uri>#.*)"(.*)<\/a>/Us',
                function ($matches) use ($chunkLevel, $linkMapper) {
                    if (isset($linkMapper[$matches['uri']])) {
                        $newUri = $linkMapper[$matches['uri']];
                        $urlBasePath = ($chunkLevel === 2) ? '../' : './';
                    } else {
                        $newUri = $matches['uri'];
                        $urlBasePath = '';
                    }

                    return sprintf('<a class="internal" href="%s%s"%s</a>', $urlBasePath, $newUri, $matches[2]);
                },
                $htmlContent
            );

            file_put_contents($bookPage->getPathname(), $htmlContent);
        }
    }

    /**
     * It marks the internal links of the book used for cross-references. This
     * allows to display the internal links differently than the regular links.
     *
     * @param ParseEvent $event The object that contains the item being processed
     */
    public function markInternalLinks(ParseEvent $parseEvent): void
    {
        // Internal links are only marked for the PDF editions
        if ($parseEvent->app->edition('format') !== 'pdf') {
            return;
        }

        $item = $parseEvent->getItem();

        $item['content'] = preg_replace_callback(
            '/<a (href="#.*".*)<\/a>/Us',
            function ($matches) {
                // First check if there is an existing class attribute before
                // adding a new class attribute and breaking the XHTML syntax
                if (preg_match('/\bclass="(.*)"/Us', $matches[1]) !== false) {
                    return '<a ' . preg_replace('/\bclass="(.*)"/Us', 'class="internal $1"', $matches[1]);
                }
                return sprintf('<a class="internal" %s</a>', $matches[1]);
            },
            $item['content']
        );

        $parseEvent->setItem($item);
    }
}
