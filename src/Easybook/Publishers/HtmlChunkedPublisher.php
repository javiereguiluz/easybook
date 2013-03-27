<?php

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Easybook\Publishers;

use Easybook\Events\EasybookEvents as Events;
use Easybook\Events\BaseEvent;

/**
 * It publishes the book as a standalone HTML website. All the internal links
 * and navigation is based on relative URLs. This means that the generated
 * book can be browsed offline or copied under any web server directory.
 *
 * The 'HtmlChunked' name was selected because that is the term traditionally
 * used by tools like DocBook (http://www.sagehill.net/docbookxsl/Chunking.html)
 */
class HtmlChunkedPublisher extends HtmlPublisher
{
    // Elements not included in this array (such as license, title, and author)
    // won't generate an HTML page in the resulting book website
    // TODO: this property should be easily configurable
    public $elementsGeneratingPages = array('appendix', 'chapter');

    public function decorateContents()
    {
        $decoratedItems = array();

        foreach ($this->app->get('publishing.items') as $item) {
            $this->app->set('publishing.active_item', $item);

            // filter the original item content before decorating it
            $event = new BaseEvent($this->app);
            $this->app->dispatch(Events::PRE_DECORATE, $event);

            // Do nothing to decorate the item

            $event = new BaseEvent($this->app);
            $this->app->dispatch(Events::POST_DECORATE, $event);

            // get again 'item' object because POST_DECORATE event can modify it
            $decoratedItems[] = $this->app->get('publishing.active_item');
        }

        $this->app->set('publishing.items', $decoratedItems);
    }

    public function assembleBook()
    {
        // TODO: the name of the chunked book directory (book/) must be configurable
        $this->app->set('publishing.dir.output', $this->app->get('publishing.dir.output').'/book');
        $this->app->get('filesystem')->mkdir($this->app->get('publishing.dir.output'));

        // generate easybook CSS file
        if ($this->app->edition('include_styles')) {
            $this->app->render(
                '@theme/style.css.twig',
                array('resources_dir' => $this->app->get('app.dir.resources').'/'),
                $this->app->get('publishing.dir.output').'/css/easybook.css'
            );
        }

        // copy custom CSS file
        $customCss = $this->app->getCustomTemplate('style.css');
        $hasCustomCss = file_exists($customCss);
        if ($hasCustomCss) {
            $this->app->get('filesystem')->copy(
                $customCss,
                $this->app->get('publishing.dir.output').'/css/styles.css',
                true
            );
        }

        // generate the chunks (HTML pages) of the published book
        $toc = $this->flattenToc();
        $this->app->set('publishing.book.toc', $toc);

        foreach ($this->app->get('publishing.items') as $item) {
            $element = $item['config']['element'];

            if (in_array($element, $this->elementsGeneratingPages)) {
                $chunkToc = $this->chunkItem($item, $toc, $hasCustomCss, $this->app->edition('chunk_level'));

                // chunk_level = 2 usually results in several book sections merged
                // into others. To take this into account in the book index page,
                // use the generated chunk toc instead of the regular book toc
                if (2 == $this->app->edition('chunk_level')) {
                    $toc = $chunkToc;
                }
            } elseif (in_array($element, array('license', 'edition', 'title', 'cover', 'author', 'toc'))) {
                // some special book items, such as the license or the author information,
                // are always reserved for the index page, instead of showing them in
                // their own single page
                // TODO: this behavior makes sense for most kind of books, but it should be configurable
                $indexItems[$element] = $item;
            }
        }

        $this->fixInternalLinks();

        // generate index page
        $this->app->render('index.twig', array(
                'items'          => $indexItems,
                'toc'            => $toc,
                'has_custom_css' => $hasCustomCss
            ),
            $this->app->get('publishing.dir.output').'/index.html'
        );

        // copy book images
        if (file_exists($imagesDir = $this->app->get('publishing.dir.contents').'/images')) {
            $this->app->get('filesystem')->mirror(
                $imagesDir,
                $this->app->get('publishing.dir.output').'/images'
            );
        }
    }

    /**
     * It turns the full nested book TOC into a simple and flatten TOC.
     * This eases the navigation between chunks because it makes trivial
     * to find the next and the previous chunk.
     */
    private function flattenToc()
    {
        $flattenedToc = array();

        $bookItems = $this->normalizePageNames($this->app->get('publishing.items'));

        // calculate the URL of each book chunk and generate the flattened TOC
        $bookSlug = $this->app->get('publishing.book.slug');
        $items = array();
        foreach ($bookItems as $item) {
            $itemToc = array();
            foreach ($item['toc'] as $chunk) {
                if (array_key_exists('level', $chunk) && 1 == $chunk['level']) {
                    $chunk['url']    = sprintf('%s.html', $item['page_name']);
                    $chunk['parent'] = null;
                    $chunk['config'] = $item['config']; // needed for templates

                    $parentChunk = $chunk;
                } elseif (array_key_exists('level', $chunk) && 2 == $chunk['level']) {
                    if (1 == $this->app->edition('chunk_level')) {
                        $chunk['url'] = sprintf('%s.html#%s', $item['page_name'], $chunk['slug']);
                    } elseif (2 == $this->app->edition('chunk_level')) {
                        $chunk['url'] = sprintf('%s/%s.html', $item['page_name'], $chunk['slug']);
                    }

                    $chunk['parent'] = $parentChunk;
                }

                $itemToc[] = $chunk;

                // only some book elements generate chunked HTML pages
                if (in_array($item['config']['element'], $this->elementsGeneratingPages)) {
                    $flattenedToc[] = $chunk;
                }
            }

            $item['toc'] = $itemToc;
            $items[] = $item;
        }

        $this->app->set('publishing.items', $items);

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
     * @param  array $items The original book items.
     *
     * @return array        The book items with their new 'page_name' property.
     */
    private function normalizePageNames($items)
    {
        $itemsWithNormalizedPageNames = array();

        foreach ($items as $item) {
            $itemPageName = $this->app->slugify($item['label'] ?: $item['slug']);
            $item['page_name'] = $itemPageName;

            $itemsWithNormalizedPageNames[] = $item;
        }

        return $itemsWithNormalizedPageNames;
    }

    /**
     * Divides an item (e.g. a chapter) into several chunks (i.e. several HTML
     * pages). This operation is quite complex because some exceptions are taken
     * into account to generate a better looking book.
     *
     * @param  array    $item         The item to be chunked.
     * @param  array    $bookToc      The full (and flatten) book TOC (table of contents)
     * @param  boolean  $hasCustomCss This flag is needed to render each chunk template
     * @param  integer  $chunkLevel   The number of chunks the book is divided into:
     *                                  * 1 means each <h1> section generates an HTML page
     *                                  * 2 means each <h1> and <h2> sections generate an HTML page
     *
     * @return  array                 The whole new (and flattened) book TOC
     */
    private function chunkItem($item, $bookToc, $hasCustomCss, $chunkLevel = 1)
    {
        if (1 == $chunkLevel) {
            return $this->generateFirstLevelChunks($item, $bookToc, $hasCustomCss);
        } elseif (2 == $chunkLevel) {
            return $this->generateSecondLevelChunks($item, $bookToc, $hasCustomCss);
        } else {
            throw new \RuntimeException("The 'chunk_level' option of the book can only be '1' or '2'");
        }
    }

    /**
     * It generates one HTML page for each book element.
     *
     * @param  array    $item         The item to be chunked.
     * @param  array    $bookToc      The whole (and flattened) book TOC
     * @param  boolean  $hasCustomCss This flag is needed to render each chunk template
     *
     * @return  array                 The whole (and flattened) book TOC (it can be
     *                                modified inside this method)
     */
    private function generateFirstLevelChunks($item, $bookToc, $hasCustomCss)
    {
        $chunkFilePath = $this->app->get('publishing.dir.output').'/'.$item['page_name'].'.html';
        $bookToc = $this->filterBookToc($bookToc);
        $itemPosition = $this->findItemPosition($item, $bookToc);

        $templateVariables = array(
            'item'     => $item,
            'toc'      => $bookToc,
            'previous' => $this->getPreviousChunk($itemPosition, $bookToc),
            'next'     => $this->getNextChunk($itemPosition, $bookToc),
            'has_custom_css' => $hasCustomCss,
        );

        // try first to render the specific template for each content
        // type, if it exists (e.g. toc.twig, chapter.twig, etc.) and
        // use chunk.twig as the fallback template
        try {
            $templateName = $item['config']['element'].'.twig';
            $this->app->render($templateName, $templateVariables, $chunkFilePath);
        } catch (\Twig_Error_Loader $e) {
            $this->app->render('chunk.twig', $templateVariables, $chunkFilePath);
        }

        return $bookToc;
    }

    /**
     * It generates several HTML pages for each book element.
     *
     * @param  array    $item         The item to be chunked.
     * @param  array    $bookToc      The whole (and flattened) book TOC
     * @param  boolean  $hasCustomCss This flag is needed to render each chunk template
     *
     * @return  array                 The whole (and flattened) book TOC (it can be
     *                                modified inside this method)
     */
    private function generateSecondLevelChunks($item, $bookToc, $hasCustomCss)
    {
        $chunks = $this->prepareItemChunks($item);

        // bookToc can be modified by the previous prepareItemChunks() method
        $bookToc = $this->app->get('publishing.book.toc');
        $bookToc = $this->filterBookToc($bookToc, 2);
        $this->app->set('publishing.book.toc', $bookToc);

        foreach ($chunks as $i => $chunk) {
            $itemPosition = $this->findItemPosition($chunk, $bookToc, 'url');

            if (1 == $chunk['level']) {
                $chunksDir = $this->app->get('publishing.dir.output').'/'.$item['page_name'];
                $chunkFilePath = $this->app->get('publishing.dir.output').'/'.$item['page_name'].'.html';
            } elseif (2 == $chunk['level']) {
                if (!file_exists($chunksDir)) {
                    $this->app->get('filesystem')->mkdir($chunksDir);
                }

                $chunkFilePath = $chunksDir.'/'.$chunk['slug'].'.html';
            }

            $templateVariables = array(
                'item'     => $chunk,
                'toc'      => $bookToc,
                'previous' => $this->getPreviousChunk($itemPosition, $bookToc),
                'next'     => $this->getNextChunk($itemPosition, $bookToc),
                'has_custom_css' => $hasCustomCss,
            );

            // try first to render the specific template for each content
            // type, if it exists (e.g. toc.twig, chapter.twig, etc.) and
            // use chunk.twig as the fallback template
            try {
                $templateName = $item['config']['element'].'.twig';
                $this->app->render($templateName, $templateVariables, $chunkFilePath);
            } catch (\Twig_Error_Loader $e) {
                $this->app->render('chunk.twig', $templateVariables, $chunkFilePath);
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
     * @param  array $item The item to be split into chunks
     *
     * @return array       The chunks the item has been split into
     */
    private function prepareItemChunks($item)
    {
        // divide the item content using '<h1>' and '<h2>' HTML sections
        $originalItemChunks = preg_split('/(<h[1-2].*<\/h[1-2]>)/', $item['content'],
            null, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        // prepare each chunk information combining the item['toc'] information
        // with the contents extracted in the $originalItemChunks variable
        $itemChunks = array();
        foreach ($item['toc'] as $i => $itemChunk) {
            if (1 == $itemChunk['level']) {
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

                    if (array_key_exists('slug', $match)
                        && $match['slug'] == $itemChunk['slug']
                        && 2 == $itemChunk['level']) {
                        $itemChunk['html_title'] = $originalItemChunks[$j];
                        $itemChunk['content']    = $originalItemChunks[$j+1];

                        $itemChunks[$i] = $itemChunk;
                    }
                }
            }
        }

        // if needed, merge the first '<h2>' section into the previous '<h1>' empty section

        if ('<h2' != substr($originalItemChunks[0], 0, 3)) {
            // if there is some content between the '<h1>' and the first '<h2>',
            // use it as the content of the first chapter page
            $itemChunks[0]['content'] = $originalItemChunks[0];
        } else {
            // there is no content between the '<h1>' and the first '<h2>'.
            // Include the first '<h2>' section inside the first chapter page
            // and delete this '<h2>' section from the book TOC
            $firstH2SectionHeading = $itemChunks[1]['html_title'];
            $firstH2SectionContent = $itemChunks[1]['content'];
            $itemChunks[0]['content'] = $firstH2SectionHeading."\n".$firstH2SectionContent;

            // look for and unset this item from the global flatten TOC
            $toc = $this->app->get('publishing.book.toc');
            foreach ($toc as $i => $entry) {
                if ($itemChunks[1]['slug'] == $entry['slug']) {
                    unset($toc[$i]);

                    // needed to recreate sequential numeric keys lost when
                    // removing the previous TOC item
                    $toc = array_values($toc);

                    $this->app->set('publishing.book.toc', $toc);

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
     * @param  array  $toc       The book TOC to filter
     * @param  integer $maxLevel Any item with a 'level' higher than this will be removed
     *
     * @return array             The filtered toc.
     */
    private function filterBookToc($toc, $maxLevel = 1)
    {
        $toc = array_filter($toc, function ($element) use ($maxLevel) {
            return $element['level'] <= $maxLevel;
        });

        // needed to recreate sequential numeric keys lost when
        // filtering the original toc
        $toc = array_values($toc);

        return $toc;
    }

    /**
     * It finds the position of the current item in the book TOC.
     *
     * @param  array $item      The item 
     * @param  array $bookToc   The whole (flattened) book TOC
     * @param  string $criteria The item field whose value is used to detect the item position
     *
     * @return integer          The numeric position of the item inside the book TOC
     */
    private function findItemPosition($item, $bookToc, $criteria = 'slug')
    {
        $position = -1;
        foreach ($bookToc as $i => $entry) {
            if (array_key_exists($criteria, $item) && $item[$criteria] == $entry[$criteria]) {
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
     * @param  integer $currentPosition The position of the current item
     * @param  array   $bookToc         The whole (flattened) book toc
     *
     * @return array                    The item that goes before the current item
     */
    private function getPreviousChunk($currentPosition, $bookToc)
    {
        $previousChunk = array_key_exists($currentPosition-1, $bookToc)
            ? $bookToc[$currentPosition-1]
            : array(
                'level' => 1,
                'slug'  => 'index',
                'url'   => 'index.html'
            );

        return $previousChunk;
    }

    /**
     * It returns the next item according to the current item position and the
     * book toc.
     *
     * @param  integer $currentPosition The position of the current item
     * @param  array   $bookToc         The whole (flattened) book toc
     *
     * @return array|null               The item that should follow the current item
     *                                  or null if this is the last chunk
     */
    private function getNextChunk($currentPosition, $bookToc)
    {
        $nextChunk = array_key_exists($currentPosition+1, $bookToc)
            ? $bookToc[$currentPosition+1]
            : null;

        return $nextChunk;
    }

    /**
     * If fixes the internal links of the book (the links that point to chapters
     * and sections of the book).
     *
     * The author of the book always uses relative links, such as:
     *   see <a href="#new-content-types">this section</a> for more information
     *
     * In order to work, the relative URIs must be replaced by absolute URIs:
     *   see <a href="../chapter3/page-slug.html#new-content-types">this section</a>
     *
     * This replacement cannot be done earlier in the book processing, because
     * books published as websites merge empty sections and the absolute URI
     * cannot be determined until the book has been completely generated.
     */
    private function fixInternalLinks()
    {
        $generatedChunks = $this->app->get('finder')
            ->files()
            ->name('*.html')
            ->in($this->app->get('publishing.dir.output'))
        ;

        // maps the original internal links (e.g. #new-content-types)
        // with the correct absolute URL needed for a website
        // (e.g. chapter-3/advanced-features.html#new-content-types
        $internalLinkMapper = array();

        // look for the ID of every book section
        foreach ($generatedChunks as $chunk) {
            $htmlContent = file_get_contents($chunk->getPathname());

            $matches = array();
            $numHeadings = preg_match_all(
                '/<h[1-6].*id="(?<id>.*)".*<\/h[1-6]>/U',
                $htmlContent, $matches, PREG_SET_ORDER
            );

            if ($numHeadings > 0) {
                foreach ($matches as $match) {
                    $relativeUri = '#'.$match['id'];
                    $absoluteUri = $chunk->getRelativePathname().$relativeUri;

                    $internalLinkMapper[$relativeUri] = $absoluteUri;
                }
            }
        }

        // replace the internal relative URIs for the mapped absolute URIs
        foreach ($generatedChunks as $chunk) {
            $htmlContent = file_get_contents($chunk->getPathname());

            // hackish method the detect if this is a first level book page
            // or a page inside a directory
            if (false === strpos($chunk->getRelativePathname(), '/')) {
                $chunkLevel = 1;
            } else {
                $chunkLevel = 2;
            }

            $htmlContent = preg_replace_callback(
                '/<a href="(?<uri>#.*)"(.*)<\/a>/Us',
                function ($matches) use ($chunkLevel, $internalLinkMapper) {
                    if (array_key_exists($matches['uri'], $internalLinkMapper)) {
                        $newUri = $internalLinkMapper[$matches['uri']];
                        $urlBasePath = 2 == $chunkLevel ? '../' : './';
                    } else {
                        $newUri = $matches['uri'];
                        $urlBasePath = '';
                    }

                    return sprintf(
                        '<a class="internal" href="%s%s"%s</a>',
                        $urlBasePath, $newUri, $matches[2]
                    );
                },
                $htmlContent
            );

            file_put_contents($chunk->getPathname(), $htmlContent);
        }
    }
}
