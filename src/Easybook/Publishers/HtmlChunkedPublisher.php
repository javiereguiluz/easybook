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

class HtmlChunkedPublisher extends HtmlPublisher
{
    public function decorateContents()
    {
        $decoratedItems = array();

        foreach ($this->app['publishing.items'] as $item) {
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
        // TODO: the elements that generate a page should be configurable
        // Elements not included in this array (such as license, title, and author)
        // won't result in a a webpage, meaning that the website version of the
        // book won't show them.
        $elementsGeneratingPages = array('appendix', 'chapter');

        // TODO: the name of the chunked book directory (book/) must be configurable
        $this->app->set('publishing.dir.output', $this->app['publishing.dir.output'].'/book');
        $this->app->get('filesystem')->mkdir($this->app['publishing.dir.output']);

        // generate easybook CSS file
        if ($this->app->edition('include_styles')) {
            $this->app->render(
                '@theme/style.css.twig',
                array('resources_dir' => $this->app['app.dir.resources'].'/'),
                $this->app['publishing.dir.output'].'/css/easybook.css'
            );
        }

        // generate custom CSS file
        $customCss = $this->app->getCustomTemplate('style.css');
        if (file_exists($customCss)) {
            $this->app->get('filesystem')->copy(
                $customCss,
                $this->app['publishing.dir.output'].'/css/styles.css',
                true
            );
        }
        // generate chunks for chapters and appendices
        $toc = $this->flattenToc();
        $this->app->set('publishing.book.toc', $toc);

        foreach ($this->app['publishing.items'] as $item) {
            $element = $item['config']['element'];

            if (in_array($element, $elementsGeneratingPages)) {
                $chunkToc = $this->chunkItem($item, $toc, file_exists($customCss), $this->app->edition('chunk_level'));

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

        // generate index page
        file_put_contents(
            $this->app['publishing.dir.output'].'/index.html',
            $this->app->render('index.twig', array(
                'items'          => $indexItems,
                'toc'            => $toc,
                'has_custom_css' => file_exists($customCss)
            ))
        );

        // copy book images
        if (file_exists($imagesDir = $this->app['publishing.dir.contents'].'/images')) {
            $this->app->get('filesystem')->mirror(
                $imagesDir,
                $this->app['publishing.dir.output'].'/images'
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

        // TODO: the elements that generate a page should be configurable
        // Elements not included in this array (such as license, title, and author)
        // won't result in a a webpage, meaning that the website version of the
        // book won't show them.
        $elementsGeneratingPages = array('appendix', 'chapter');

        // The pages of chapters and appendixes aren't named after their original
        // slugs. easybook follows the much more usual practice of naming them
        // using their labels. Therefore, chapter 1 page will be named
        // chapter-1.html instead of introduction-to-lorem-ipsum.html
        // The only exception happens when the book has disabled labels. Then,
        // the item slug is used.
        $items = array();
        foreach ($this->app['publishing.items'] as $item) {
            $newSlug = $this->app->get('slugger')->slugify(trim($item['label'] ?: $item['slug']), null, null, false);
            $item['slug'] = $newSlug;
            $item['toc'][0]['slug'] = $newSlug;

            $items[] = $item;
        }

        // calculate the absolute URL of each book chunk and generate the flattened TOC
        $bookSlug = $this->app->get('publishing.book.slug');
        $previousItems = $items;
        $items = array();
        foreach ($previousItems as $item) {
            $itemToc = array();
            foreach ($item['toc'] as $chunk) {
                if (array_key_exists('level', $chunk) && 1 == $chunk['level']) {
                    // set the absolute URL of the chunk
                    $chunk['url'] = sprintf('/%s/%s.html', $bookSlug, $chunk['slug']);

                    $chunk['parent'] = null;
                    $parentChunk = $chunk;

                    // item 'config' information may come handy in the templates
                    $chunk['config'] = $item['config'];
                } elseif (array_key_exists('level', $chunk) && 2 == $chunk['level']) {
                    // set the absolute URL of the chunk
                    if (1 == $this->app->edition('chunk_level')) {
                        $chunk['url'] = sprintf('/%s/%s.html#%s', $bookSlug, $parentChunk['slug'], $chunk['slug']);
                    } elseif (2 == $this->app->edition('chunk_level')) {
                        $chunk['url'] = sprintf('/%s/%s/%s.html', $bookSlug, $parentChunk['slug'], $chunk['slug']);
                    }

                    $chunk['parent'] = $parentChunk;
                }

                $itemToc[] = $chunk;

                // only some book elements generate chunked HTML pages
                if (in_array($item['config']['element'], $elementsGeneratingPages)) {
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
     * Divides an item into several chunks. This operation is quite complex
     * because some exceptions are taken into account to generate a better
     * looking book. In essence, some <h2> sections don't generate their own
     * page because they are included in the previous (and empty) '<h1>' section.
     * This is explained bellow with high detail.
     *
     * @param  array    $item         The item to be chunked.
     * @param  array    $toc          The full (and flatten) book TOC
     * @param  boolean  $hasCustomCss This flag is needed to render each chunk template
     * @param  integer  $level        The chunk level of the book:
     *                                  * 1 means each <h1> section generates an HTML page
     *                                  * 2 means each <h1> and <h2> sections generate an HTML page
     * @return  array   $toc          The toc of the chunk (it may be different than the input $toc
     *                                because some sections are automatically merged)
     */
    private function chunkItem($item, $toc, $hasCustomCss, $level = 1)
    {
        // $level = 1 means that each <h1> section generates an HTML page
        if (1 == $level) {
            $chunkPath = $this->app['publishing.dir.output'].'/'.$item['slug'].'.html';

            // filter the flatten TOC to only consider level 1 elements
            $toc = array_filter($toc, function ($element) {
                return 1 == $element['level'];
            });
            // needed to recreate sequential numeric keys lost when
            // filtering the original TOC
            $toc = array_values($toc);

            // look for this item in the flatten TOC
            $position = -1;
            foreach ($toc as $i => $entry) {
                if ($item['slug'] == $entry['slug']) {
                    $position = $i;
                    break;
                }
            }

            // calculate the URL of the previous and next items
            $previous = array_key_exists($position-1, $toc)
                ? $toc[$position-1]
                : array(
                    'level' => 1,
                    'slug'  => 'index',
                    'url'   => sprintf('/%s/index.html', $this->app->get('publishing.book.slug'))
                );

            $next = array_key_exists($position+1, $toc)
                ? $toc[$position+1]
                : null;

            $chunkContent = $this->app->render('chunk.twig', array(
                'item'     => $item,
                'toc'      => $toc,
                'previous' => $previous,
                'next'     => $next,
                'has_custom_css' => $hasCustomCss,
            ));

            file_put_contents($chunkPath, $chunkContent);

            return $toc;
        }

        // $level = 2 means that each <h1> and <h2> sections generate an HTML page
        if (2 == $level) {
            // divide the chapter/appendix full content into the section chunks
            $allChunks = preg_split('/(<h[1-2].*<\/h[1-2]>)/', $item['content'], null, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

            // prepare each chunk information combining the item['toc'] information
            // with the contents extracted in the $allChunks variable
            $itemChunks = array();
            $parentChunk = null;
            foreach ($item['toc'] as $i => $itemChunk) {
                if (1 == $itemChunk['level']) {
                    // include the chapter/appendix TOC in the first level-1 chunk
                    // this is useful for the template rendering done later
                    $itemChunk['toc'] = $item['toc'];

                    $itemChunks[$i] = $itemChunk;
                } else {
                    // instead of matching each chunk position in the TOC with its
                    // position in $allChunks, it's safer to perform a match with
                    // the chunk slug
                    foreach ($allChunks as $j => $chunk) {
                        // extract the slug of this chunk from its <h2> heading
                        preg_match('/<h2.*id="(?<slug>.*)".*<\/h2>/', $chunk, $match);

                        if (array_key_exists('slug', $match) && $match['slug'] == $itemChunk['slug']) {
                            $itemChunk['html_title'] = $allChunks[$j];
                            $itemChunk['content'] = $allChunks[$j+1];

                            $itemChunks[$i] = $itemChunk;
                        }
                    }
                }
            }

            // The content of the first chapter page is very special. If there
            // are no contents between the chapter title and the first <h2> section,
            // the first <h2> section is included as part of the first chapter page.
            // In other words, if the chapter is as follows:
            //
            //   <h1>1. Lorem ipsum</h2>
            //   (no contents)
            //
            //   <h2>1.1 Ipsum lorem</h2>
            //   Lorem ...
            //
            //   <h2>1.2 Other ipsum lorem</h2>
            //   Lorem ...
            //
            //   This chapter will result in the following two HTML pages:
            //
            //   chapter-1.html
            //     <h1>1. Lorem ipsum</h2>
            //     <h2>1.1 Ipsum lorem</h2>
            //     Lorem ...
            //
            //   chapter-1/other-ipsum-lorem.html
            //     <h2>1.2 Other ipsum lorem</h2>
            //     Lorem ...
            //
            //
            //   However, if the chapter is as follows:
            //
            //   <h1>1. Lorem ipsum</h2>
            //   Lorem ...
            //
            //   <h2>1.1 Ipsum lorem</h2>
            //   Lorem ...
            //
            //   <h2>1.2 Other ipsum lorem</h2>
            //   Lorem ...
            //
            //   This chapter will result in the following two HTML pages:
            //
            //   chapter-1.html
            //     <h1>1. Lorem ipsum</h2>
            //     Lorem ...
            //
            //   chapter-1/ipsum-lorem.html
            //     <h2>1.1 Ipsum lorem</h2>
            //     Lorem ...
            //
            //   chapter-1/other-ipsum-lorem.html
            //     <h2>1.2 Other ipsum lorem</h2>
            //     Lorem ...
            if ('<h2' != substr($allChunks[0], 0, 3)) {
                // there is some content between the '<h1>' and the first '<h2>'
                // Use it as the content of the first chapter page
                $itemChunks[0]['content'] = $allChunks[0];
            } else {
                // there is no content between the '<h1>' and the first '<h2>'
                // Include the first '<h2>' section inside the first chapter page
                // and delete this '<h2>' section from the book TOC
                $firstH2SectionHeading = $itemChunks[1]['html_title'];
                $firstH2SectionContent = $itemChunks[1]['content'];
                $itemChunks[0]['content'] = $firstH2SectionHeading."\n".$firstH2SectionContent;

                // look for and unset this item from the global flatten TOC
                foreach ($toc as $i => $entry) {
                    if ($itemChunks[1]['slug'] == $entry['slug']) {
                        unset($toc[$i]);

                        // needed to recreate sequential numeric keys lost when
                        // removing the previous TOC item
                        $toc = array_values($toc);
                        break;
                    }
                }

                // unset this chunk for the item chunk list
                unset($itemChunks[1]);
            }

            // needed to recreate sequential numeric keys lost if some elements
            // have been removed from the $itemChunks array
            $itemChunks = array_values($itemChunks);

            // create a single HTML page for each chunk
            foreach ($itemChunks as $i => $itemChunk) {
                // prepare each chunk 'parent' as this information is needed
                // later to get the next and previous URLs
                if (1 == $itemChunk['level']) {
                    $parentChunk = $item;
                } elseif (2 == $itemChunk['level']) {
                    $itemChunk['parent'] = $parentChunk;
                }

                // filter the flatten TOC to only consider level 1 and 2 elements
                $toc = array_filter($toc, function ($element) {
                    return 1 === $element['level'] || 2 === $element['level'];
                });
                // needed to recreate sequential numeric keys lost when
                // filtering the original TOC
                $toc = array_values($toc);

                // update the book TOC with this new filtered TOC
                $this->app->set('publishing.book.toc', $toc);

                // look for this item in the flatten TOC (to get 'next' and 'previous' items)
                $position = -1;
                foreach ($toc as $i => $entry) {
                    if (array_key_exists('url', $itemChunk) && $itemChunk['url'] == $entry['url']) {
                        $position = $i;
                        break;
                    }
                }

                if (1 == $itemChunk['level']) {
                    // this variable is needed for the chunks of level 2 generated later
                    $chunkDirectory = $this->app['publishing.dir.output'].'/'.$itemChunk['slug'];
                    $chunkPath = $this->app['publishing.dir.output'].'/'.$itemChunk['slug'].'.html';

                    // get the previous and next items
                    $previous = array_key_exists($position-1, $toc)
                        ? $toc[$position-1]
                        : array(
                            'level' => 1,
                            'slug'  => 'index',
                            'url'   => sprintf('/%s/index.html', $this->app->get('publishing.book.slug'))
                        );

                    $next = array_key_exists($position+1, $toc)
                        ? $toc[$position+1]
                        : null;
                }
                elseif (2 == $itemChunk['level']) {
                    if (!file_exists($chunkDirectory)) {
                        $this->app->get('filesystem')->mkdir($chunkDirectory);
                    }

                    $chunkPath = $chunkDirectory.'/'.$itemChunk['slug'].'.html';

                    // get the previous and next items
                    $previous = array_key_exists($position-1, $toc)
                        ? $toc[$position-1]
                        : array(
                            'level' => 1,
                            'slug'  => 'index',
                            'url'   => sprintf('/%s/index.html', $this->app->get('publishing.book.slug'))
                        );

                    $next = array_key_exists($position+1, $toc)
                        ? $toc[$position+1]
                        : null;
                }

                $chunkContent = $this->app->render('chunk.twig', array(
                    'item'     => $itemChunk,
                    'toc'      => $toc,
                    'previous' => $previous,
                    'next'     => $next,
                    'has_custom_css' => $hasCustomCss,
                ));

                file_put_contents($chunkPath, $chunkContent);
            }
        }

        return $toc;
    }
}
