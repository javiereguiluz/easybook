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

use Easybook\Parsers\MdParser;
use Easybook\Events\EasybookEvents as Events;
use Easybook\Events\ParseEvent;

class HtmlChunkedPublisher extends HtmlPublisher
{
    public function decorateContents()
    {
        // Do nothing
    }

    public function assembleBook()
    {
        // TODO: the elements that generate a page should be configurable
        $elementsGeneratingPages = array('appendix', 'chapter');

        // TODO: the name of the chunked book directory (book/) must be configurable
        $this->app->set('publishing.dir.output', $this->app['publishing.dir.output'].'/book');

        // generate easybook CSS file
        if ($this->app->edition('include_styles')) {
            $this->app->renderThemeTemplate(
                'style.css.twig',
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

        // prepare chunked TOC
        $items = array();
        $nav   = array();
        foreach ($this->app['publishing.items'] as $item) {
            // HTML pages are named after chunk's slug
            // New slugs are automatic (chapter-1, chapter-2, ...) instead of custom (intro-to-...)
            $slug = $this->app->get('slugger')->slugify(trim($item['label']), '', false);
            $item['slug'] = $slug;
            $items[] = $item;

            if (in_array($item['config']['element'], $elementsGeneratingPages)) {
                $nav[] = $slug;
            }
        }

        // update `publishing items` with the new slug value
        $this->app->set('publishing.items', $items);

        // generate chunks for chapters and appendices
        $items = array();
        $chunkNumber = 0;
        foreach ($this->app['publishing.items'] as $item) {
            $element = $item['config']['element'];

            if (in_array($element, $elementsGeneratingPages)) {
                $chunkPath = $this->app['publishing.dir.output'].'/'.$item['slug'].'.html';

                $chunkContent = $this->app->render('chunk.twig', array(
                    'item'           => $item,
                    'has_custom_css' => file_exists($customCss),
                    'previous' => array_key_exists($chunkNumber-1, $nav)
                                      ? $nav[$chunkNumber-1]
                                      : null,
                    'next'     => array_key_exists($chunkNumber+1, $nav)
                                      ? $nav[$chunkNumber+1]
                                      : null
                ));

                file_put_contents($chunkPath, $chunkContent);

                $chunkNumber++;
            }
            elseif (in_array($element, array('license', 'edition', 'title', 'cover', 'author', 'toc'))) {
                $indexItems[$element] = $item;
            }

            $items[] = $item;
        }

        // generate index page
        file_put_contents(
            $this->app['publishing.dir.output'].'/index.html',
            $this->app->render('index.twig', array(
                'items'          => $indexItems,
                'has_custom_css' => file_exists($customCss),
                'next'           => array(
                    'url'   => 'capitulo-1.html',
                    'title' => 'Índice de contenidos'
                )
            ))
        );

        // copy book images
        $this->app->get('filesystem')->mirror(
            $this->app['publishing.dir.contents'].'/images',
            $this->app['publishing.dir.output'].'/images'
        );
    }
}