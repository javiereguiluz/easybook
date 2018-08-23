<?php declare(strict_types=1);

namespace Easybook\Publishers;

use Easybook\Events\EasybookEvents as Events;
use RuntimeException;
use Symfony\Component\EventDispatcher\Event;
use Twig_Error_Loader;

/**
 * It publishes the book as a standalone HTML website. All the internal links
 * and navigation is based on relative URLs. This means that the generated
 * book can be browsed offline or copied under any web server directory.
 *
 * The 'HtmlChunked' name was selected because that is the term traditionally
 * used by tools like DocBook (http://www.sagehill.net/docbookxsl/Chunking.html)
 */
final class HtmlChunkedPublisher extends AbstractPublisher
{
    // these elements are so special that they cannot define a TOC

    /**
     * @var string[]
     */
    private $elementsWithoutToc = ['cover', 'toc'];

    /**
     * Overrides the base publisher method to avoid the decoration of the book items.
     * Instead of using the regular Twig templates based on the item type (e.g. chapter),
     * the items of the books published as websites are decorated afterwards with some
     * special Twig templates.
     */
    public function decorateContents(): void
    {
        $decoratedItems = [];

        foreach ($this->app['publishing.items'] as $item) {
            $this->app['publishing.active_item'] = $item;

            // filter the original item content before decorating it
            $this->eventDispatcher->dispatch(Events::PRE_DECORATE, new Event());

            // Do nothing to decorate the item
            $this->eventDispatcher->dispatch(Events::POST_DECORATE, new Event());

            // get again 'item' object because POST_DECORATE event can modify it
            $decoratedItems[] = $this->app['publishing.active_item'];
        }

        $this->app['publishing.items'] = $decoratedItems;
    }

    public function assembleBook(): void
    {
        $this->app['publishing.dir.output'] = $this->app['publishing.dir.output'] . '/book';
        $this->filesystem->mkdir($this->app['publishing.dir.output']);

        // generate easybook CSS file
        if ($this->app->edition('include_styles')) {
            $this->renderer->renderToFile(
                '@theme/style.css.twig',
                ['resources_dir' => $this->app['app.dir.resources'] . '/'],
                $this->app['publishing.dir.output'] . '/css/easybook.css'
            );
        }

        // copy custom CSS file
        $customCss = $this->app->getCustomTemplate('style.css');
        $hasCustomCss = file_exists($customCss);
        if ($hasCustomCss) {
            $this->filesystem->copy($customCss, $this->app['publishing.dir.output'] . '/css/styles.css', true);
        }

        // generate the chunks (HTML pages) of the published book
        $toc = $this->flattenToc();
        $this->app['publishing.book.toc'] = $toc;

        foreach ($this->app['publishing.items'] as $item) {
            if (! in_array($item['config']['element'], $this->elementsWithoutToc, true)) {
                $chunkToc = $this->chunkItem($item, $toc, $hasCustomCss, $this->app->edition('chunk_level'));

                // chunk_level = 2 usually results in several book sections merged
                // into others. To take this into account in the book index page,
                // use the generated chunk toc instead of the regular book toc
                if ($this->app->edition('chunk_level') === 2) {
                    $toc = $chunkToc;
                }
            }
        }

        // generate index page
        $this->renderer->renderToFile(
            'index.twig',
            [
                'items' => $this->app['publishing.items'],
                'toc' => $toc,
                'has_custom_css' => $hasCustomCss,
            ],
            $this->app['publishing.dir.output'] . '/index.html'
        );

        // copy book images
        $imagesDir = $this->app['publishing.dir.contents'] . '/images';
        if (file_exists($imagesDir)) {
            $this->filesystem->mirror($imagesDir, $this->app['publishing.dir.output'] . '/images');
        }
    }

    public function getFormat(): string
    {
        return 'html_chunked';
    }

    /**
     * It turns the full nested book TOC into a simple and flatten TOC.
     * This eases the navigation between chunks because it makes trivial
     * to find the next and the previous chunk.
     */
    private function flattenToc()
    {
        $flattenedToc = [];

        $bookItems = $this->normalizePageNames($this->app['publishing.items']);
        $bookItems = $this->fixItemsWithEmptyTocs($bookItems);

        // calculate the URL of each book chunk and generate the flattened TOC
        $items = [];
        foreach ($bookItems as $item) {
            $itemToc = [];
            foreach ($item['toc'] as $chunk) {
                // first-level headers
                if (isset($chunk['level']) && $chunk['level'] === 1) {
                    $chunk['url'] = sprintf('%s.html', $item['page_name']);
                    $chunk['parent'] = null;
                    $chunk['config'] = $item['config']; // needed for templates

                    $parentChunk = $chunk;
                // second-level headers
                } elseif (isset($chunk['level']) && $chunk['level'] === 2) {
                    if ($this->app->edition('chunk_level') === 1) {
                        $chunk['url'] = sprintf('%s.html#%s', $item['page_name'], $chunk['slug']);
                    } elseif ($this->app->edition('chunk_level') === 2) {
                        $chunk['url'] = sprintf('%s/%s.html', $item['page_name'], $chunk['slug']);
                    }

                    $chunk['parent'] = $parentChunk;
                // third to sixth level headers
                } elseif (isset($chunk['level'])) {
                    if ($this->app->edition('chunk_level') === 1) {
                        $chunk['url'] = sprintf('%s.html#%s', $item['page_name'], $chunk['slug']);
                    } elseif ($this->app->edition('chunk_level') === 2) {
                        $chunk['url'] = sprintf(
                            '%s/%s.html#%s',
                            $item['page_name'],
                            $parentChunk['slug'],
                            $chunk['slug']
                        );
                    }

                    $chunk['parent'] = $parentChunk;
                }

                $itemToc[] = $chunk;

                $flattenedToc[] = $chunk;
            }

            $item['toc'] = $itemToc;
            $items[] = $item;
        }

        $this->app['publishing.items'] = $items;

        return $flattenedToc;
    }

    /**
     * The generated HTML pages aren't named after the items' original slugs
     * (e.g. introduction-to-lorem-ipsum.html) but using their content types
     * and numbers (e.g. chapter-1.html).
     *
     * This method creates a new property for each item called 'page_name' which
     * stores the normalized page name that should have this item.
     *
     * @param array $items The original book items.
     *
     * @return array The book items with their new 'page_name' property.
     */
    private function normalizePageNames(array $items): array
    {
        $itemsWithNormalizedPageNames = [];

        foreach ($items as $item) {
            $itemPageName = $this->app->slugify($item['label'] ?: $item['slug']);
            $item['page_name'] = $itemPageName;

            $itemsWithNormalizedPageNames[] = $item;
        }

        return $itemsWithNormalizedPageNames;
    }

    /**
     * Special items such as 'lot' (list of tables) and 'lof' (list of figures)
     * don't have a real content. Therefore, these items have an empty TOC
     * that prevents them from appearing in the published book.
     *
     * This method ensures that every book item defines a TOC by adding a simple
     * TOC to any item without one.
     *
     * @param array $items The original book items.
     *
     * @return array The book items with their new TOCs.
     */
    private function fixItemsWithEmptyTocs(array $items): array
    {
        $itemsWithFixedTocs = [];

        foreach ($items as $item) {
            if (empty($item['toc']) && ! in_array($item['config']['element'], $this->elementsWithoutToc, true)) {
                $item['toc'] = [
                    [
                        'level' => 1,
                        'title' => $item['title'],
                        'slug' => $item['slug'],
                        'label' => '',
                        'url' => $item['page_name'] . '.html',
                        'parent' => null,
                    ],
                ];
            }

            $itemsWithFixedTocs[] = $item;
        }

        return $itemsWithFixedTocs;
    }

    /**
     * Divides an item (e.g. a chapter) into several chunks (i.e. several HTML
     * pages). This operation is quite complex because some exceptions are taken
     * into account to generate a better looking book.
     *
     * @param array $item         The item to be chunked.
     * @param array $bookToc      The full (and flatten) book TOC (table of contents)
     * @param bool  $hasCustomCss This flag is needed to render each chunk template
     * @param int   $chunkLevel   The number of chunks the book is divided into:
     *                            * 1 means each <h1> section generates an HTML page
     *                            * 2 means each <h1> and <h2> sections generate an HTML page
     *
     * @throws \RuntimeException
     *
     * @return array The whole new (and flattened) book TOC
     */
    private function chunkItem(array $item, array $bookToc, bool $hasCustomCss, int $chunkLevel = 1): array
    {
        if ($chunkLevel === 1) {
            return $this->generateFirstLevelChunks($item, $bookToc, $hasCustomCss);
        } elseif ($chunkLevel === 2) {
            return $this->generateSecondLevelChunks($item, $hasCustomCss);
        }
        throw new RuntimeException("The 'chunk_level' option of the book can only be '1' or '2'");
    }

    /**
     * It generates one HTML page for each book element.
     *
     * @param array $item         The item to be chunked.
     * @param array $bookToc      The whole (and flattened) book TOC
     * @param bool  $hasCustomCss This flag is needed to render each chunk template
     *
     * @return array The whole (and flattened) book TOC (it can be
     *               modified inside this method)
     */
    private function generateFirstLevelChunks(array $item, array $bookToc, bool $hasCustomCss): array
    {
        $chunkFilePath = $this->app['publishing.dir.output'] . '/' . $item['page_name'] . '.html';
        $bookToc = $this->filterBookToc($bookToc);
        $itemPosition = $this->findItemPosition($item, $bookToc);

        $templateVariables = [
            'item' => $item,
            'toc' => $bookToc,
            'previous' => $this->getPreviousChunk($itemPosition, $bookToc),
            'next' => $this->getNextChunk($itemPosition, $bookToc),
            'has_custom_css' => $hasCustomCss,
        ];

        // try first to render the specific template for each content
        // type, if it exists (e.g. toc.twig, chapter.twig, etc.) and
        // use chunk.twig as the fallback template
        try {
            $templateName = $item['config']['element'] . '.twig';
            $this->renderer->renderToFile($templateName, $templateVariables, $chunkFilePath);
        } catch (Twig_Error_Loader $e) {
            $this->renderer->renderToFile('chunk.twig', $templateVariables, $chunkFilePath);
        }

        return $bookToc;
    }

    /**
     * It generates several HTML pages for each book element.
     *
     * @param array $item         The item to be chunked.
     * @param bool  $hasCustomCss This flag is needed to render each chunk template
     *
     * @return array The whole (and flattened) book TOC (it can be
     *               modified inside this method)
     */
    private function generateSecondLevelChunks(array $item, bool $hasCustomCss): array
    {
        $chunks = $this->prepareItemChunks($item);

        // bookToc can be modified by the previous prepareItemChunks() method
        $bookToc = $this->app['publishing.book.toc'];
        $bookToc = $this->filterBookToc($bookToc, 2);
        $this->app['publishing.book.toc'] = $bookToc;

        foreach ($chunks as $chunk) {
            $itemPosition = $this->findItemPosition($chunk, $bookToc, 'url');

            if ($chunk['level'] === 1) {
                $chunksDir = $this->app['publishing.dir.output'] . '/' . $item['page_name'];
                $chunkFilePath = $this->app['publishing.dir.output'] . '/' . $item['page_name'] . '.html';
            } elseif ($chunk['level'] === 2) {
                if (! file_exists($chunksDir)) {
                    $this->filesystem->mkdir($chunksDir);
                }

                $chunkFilePath = $chunksDir . '/' . $chunk['slug'] . '.html';
            }

            $templateVariables = [
                'item' => $chunk,
                'toc' => $bookToc,
                'previous' => $this->getPreviousChunk($itemPosition, $bookToc),
                'next' => $this->getNextChunk($itemPosition, $bookToc),
                'has_custom_css' => $hasCustomCss,
            ];

            // try first to render the specific template for each content
            // type, if it exists (e.g. toc.twig, chapter.twig, etc.) and
            // use chunk.twig as the fallback template
            try {
                $templateName = $item['config']['element'] . '.twig';
                $this->renderer->renderToFile($templateName, $templateVariables, $chunkFilePath);
            } catch (Twig_Error_Loader $e) {
                $this->renderer->renderToFile('chunk.twig', $templateVariables, $chunkFilePath);
            }
        }

        return $bookToc;
    }

    /**
     * Splits the content of the given item into several chunks. This process
     * is quite complex because some exceptions are taken into account to
     * generate a better looking book.
     *
     * In essence, the first item page is very special. If there are no contents
     * between the chapter title and the first <h2> section, this second level
     * section is included as part of the first chapter page.
     *
     * If the chapter is as follows:      2 HTML pages are generated
     * -----------------------------      ------------------------------------
     *   <h1>1. Lorem ipsum</h2>          (1) chapter-1.html
     *   (no contents)                          <h1>1. Lorem ipsum</h2>
     *   <h2>1.1 Ipsum lorem</h2>               <h2>1.1 Ipsum lorem</h2>
     *   Lorem ...                              Lorem ...
     *   <h2>1.2 Other ipsum lorem</h2>   (2) chapter-1/other-ipsum-lorem.html
     *   Lorem ...                              <h2>1.2 Other ipsum lorem</h2>
     *                                          Lorem ...
     *
     * If the chapter is as follows:      3 HTML pages are generated
     * -----------------------------      ---------------------------------
     *   <h1>1. Lorem ipsum</h2>          (1) chapter-1.html
     *   Lorem ...                              <h1>1. Lorem ipsum</h2>
     *   <h2>1.1 Ipsum lorem</h2>               Lorem ...
     *   Lorem ...                        (2) chapter-1/ipsum-lorem.html
     *   <h2>1.2 Other ipsum lorem</h2>         <h2>1.1 Ipsum lorem</h2>
     *   Lorem ...                              Lorem ...
     *                                    (3) chapter-1/other-ipsum-lorem.html
     *                                          <h2>1.2 Other ipsum lorem</h2>
     *                                          Lorem ...
     *
     * @param array $item The item to be split into chunks
     *
     * @return array The chunks the item has been split into
     */
    private function prepareItemChunks(array $item): array
    {
        // divide the item content using '<h1>' and '<h2>' HTML sections
        $originalItemChunks = preg_split(
            '/(<h[1-2].*<\/h[1-2]>)/',
            $item['content'],
            null,
            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
        );

        // prepare each chunk information combining the item['toc'] information
        // with the contents extracted in the $originalItemChunks variable
        $itemChunks = [];
        foreach ($item['toc'] as $i => $itemChunk) {
            if ($itemChunk['level'] === 1) {
                // include the item TOC in the first level-1 chunk
                // this is useful for the template rendering done later
                $itemChunk['toc'] = $item['toc'];

                $itemChunks[$i] = $itemChunk;
            } else {
                // instead of matching each chunk position in the TOC with its
                // position in $originalItemChunks, it's safer to perform a match with
                // the chunk slug
                foreach ($originalItemChunks as $j => $chunk) {
                    // extract the slug of this chunk from its <h2> heading
                    preg_match('/<h2.*id="(?<slug>.*)".*<\/h2>/', $chunk, $match);

                    if (isset($match['slug']) && $match['slug'] === $itemChunk['slug'] && $itemChunk['level'] === 2) {
                        $itemChunk['html_title'] = $originalItemChunks[$j];
                        $itemChunk['content'] = $originalItemChunks[$j + 1];

                        $itemChunks[$i] = $itemChunk;
                    }
                }
            }
        }

        // if needed, merge the first '<h2>' section into the previous '<h1>' empty section

        if (substr($originalItemChunks[0], 0, 3) !== '<h2') {
            // if there is some content between the '<h1>' and the first '<h2>',
            // use it as the content of the first chapter page
            $itemChunks[0]['content'] = $originalItemChunks[0];
        } else {
            // there is no content between the '<h1>' and the first '<h2>'.
            // Include the first '<h2>' section inside the first chapter page
            // and delete this '<h2>' section from the book TOC
            $firstH2SectionHeading = $itemChunks[1]['html_title'];
            $firstH2SectionContent = $itemChunks[1]['content'];
            $itemChunks[0]['content'] = $firstH2SectionHeading . "\n" . $firstH2SectionContent;

            // look for and unset this item from the global flatten TOC
            $toc = $this->app['publishing.book.toc'];
            foreach ($toc as $i => $entry) {
                if ($itemChunks[1]['slug'] === $entry['slug']) {
                    unset($toc[$i]);

                    // needed to recreate sequential numeric keys lost when
                    // removing the previous TOC item
                    $toc = array_values($toc);

                    $this->app['publishing.book.toc'] = $toc;

                    break;
                }
            }

            // unset this chunk for the item chunk list
            unset($itemChunks[1]);

            // needed to recreate sequential numeric keys lost if some elements
            // have been removed from the $itemChunks array
            $itemChunks = array_values($itemChunks);
        }

        return $itemChunks;
    }

    /**
     * It filters the given book toc to remove any element with a 'level'
     * greater than the given $maxLevel value.
     *
     * @param array $toc      The book TOC to filter
     * @param int   $maxLevel Any item with a 'level' higher than this will be removed
     *
     * @return array The filtered toc.
     */
    private function filterBookToc(array $toc, int $maxLevel = 1): array
    {
        $toc = array_filter($toc, function ($element) use ($maxLevel) {
            return $element['level'] <= $maxLevel;
        });

        // needed to recreate sequential numeric keys lost when
        // filtering the original toc
        return array_values($toc);
    }

    /**
     * It finds the position of the current item in the book TOC.
     *
     * @param array  $bookToc  The whole (flattened) book TOC
     * @param string $criteria The item field whose value is used to detect the item position
     * @return int The numeric position of the item inside the book TOC
     */
    private function findItemPosition(array $item, array $bookToc, string $criteria = 'slug'): int
    {
        $position = -1;
        foreach ($bookToc as $i => $entry) {
            if (isset($item[$criteria]) && $item[$criteria] === $entry[$criteria]) {
                $position = $i;
                break;
            }
        }

        return $position;
    }

    /**
     * It returns the previous item according to the current item position and
     * the book toc.
     *
     * @param int   $currentPosition The position of the current item
     * @param array $bookToc         The whole (flattened) book toc
     *
     * @return array The item that goes before the current item
     */
    private function getPreviousChunk(int $currentPosition, array $bookToc): array
    {
        return $bookToc[$currentPosition - 1]
            ?? [
                'level' => 1,
                'slug' => 'index',
                'url' => 'index.html',
            ];
    }

    /**
     * It returns the next item according to the current item position and the
     * book toc.
     *
     * @param int   $currentPosition The position of the current item
     * @param array $bookToc         The whole (flattened) book toc
     *
     * @return array|null The item that should follow the current item
     *                    or null if this is the last chunk
     */
    private function getNextChunk(int $currentPosition, array $bookToc): ?array
    {
        return $bookToc[$currentPosition + 1]
            ?? null;
    }
}
