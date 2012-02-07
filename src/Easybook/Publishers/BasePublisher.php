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

class BasePublisher
{
    protected $app;
    
    public function __construct($app)
    {
        $this->app = $app;

        $this->prepareOutputDir();
    }

    public function publishBook()
    {
        $this->loadContents();
        $this->parseContents();
        $this->decorateContents();
        $this->assembleBook();
    }

    public function prepareOutputDir()
    {
        $bookOutputdir = $this->app['publishing.dir.book'].'/Output';
        if (!file_exists($bookOutputdir)) {
            mkdir($bookOutputdir);
            // TODO: edge case -> output dir cannot be created
        }

        $editionOutputdir = $bookOutputdir.'/'.$this->app['publishing.edition'];

        if (!file_exists($editionOutputdir)) {
            mkdir($editionOutputdir);
            // TODO: edge case -> output dir cannot be created
        }

        $this->app->set('publishing.dir.output', $editionOutputdir);
    }


    public function loadContents()
    {
        // TODO: extensibility -> editions can redefine book contents (to remove or reorder items)

        foreach ($this->app->book('contents') as $content) {
            $item = $this->initializeItem($content);

            // content define its own content file (usually: chapters, appendices)
            if (array_key_exists('content', $content)) {
                // TODO: extensibility -> contents could be written in several formats simultaneously
                // (e.g. Twig *and* Markdown)
                $path = $this->app['publishing.dir.contents'].'/'.$content['content'];
                $item['original'] = file_get_contents($path);
                $item['config']['format'] = pathinfo($path, PATHINFO_EXTENSION);
            }

            // special contents (cover, title, toc, section, ...) can, but usually don't, define their own content file
            // in this case, use the default content for these contents
            else {
                $outputFormat = $this->app->edition('format');
                $path = $this->app['app.dir.theme_'.$outputFormat].'/Contents/';
                $template = $content['element'].'.md.twig';

                $this->app->set('twig.path', $path);
                $item['original'] = $this->app->render($template);

                $item['config']['content'] = pathinfo($path, PATHINFO_FILENAME);
                $item['config']['format']  = 'md';
            }

            $this->app->append('publishing.items', $item);
        }

        // reset twig path variable to not interfere with further renderings
        $this->app->set('twig.path', null);
    }

    private function initializeItem($element)
    {
        $item = array();

        $item['config'] = array(
            'content' => '',
            'element' => '',
            'format'  => '',
            'number'  => '',
            'title'   => ''
        );

        $item['config'] = array_replace($item['config'], $element);

        $item['content']  = '';
        $item['label']    = '';
        $item['original'] = '';
        $item['slug']     = '';
        $item['title']    = '';
        $item['toc']      = array();

        if ('' != $item['config']['title']) {
            $item['title'] = $item['config']['title'];
            $item['slug']  = $this->app->get('slugger')->slugify($item['title']);
        }

        return $item;
    }
}