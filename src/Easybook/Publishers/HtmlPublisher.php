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

class HtmlPublisher extends BasePublisher
{
    public function parseContents()
    {
        $parsedItems = array();

        foreach ($this->app['publishing.items'] as $item) {
            // filter the original item content before parsing it
            $this->app->set('publishing.parsing_item', $item);

            $event = new ParseEvent($this->app);
            $this->app->dispatch(Events::PRE_PARSE, $event);

            // get again 'item' object because PRE_PARSE event can modify it
            $item = $this->app->get('publishing.parsing_item');
            $parsed = $this->app->get('parser')->parse($item['original']);

            $item['content'] = $parsed['content'];
            $item['toc']     = $parsed['toc'];

            $this->app->set('publishing.parsing_item', $item);

            $event = new ParseEvent($this->app);
            $this->app->dispatch(Events::POST_PARSE, $event);

            // get again 'item' object because POST_PARSE event can modify it
            $parsedItems[] = $this->app->get('publishing.parsing_item');
        }

        $this->app->set('publishing.items', $parsedItems);
    }

    public function decorateContents()
    {
        $decoratedItems = array();

        foreach ($this->app['publishing.items'] as $item) {
            $item['content'] = $this->app->render($item['config']['element'].'.twig', array(
                'item' => $item
            ));

            $decoratedItems[] = $item;
        }

        $this->app->set('publishing.items', $decoratedItems);
    }

    public function assembleBook()
    {
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

        // implode all the contents to create the whole book
        $book = $this->app->render('book.twig', array(
            'items'          => $this->app['publishing.items'],
            'has_custom_css' => file_exists($customCss)
        ));

        // TODO: the name of the book file (book.html) must be configurable
        file_put_contents($this->app['publishing.dir.output'].'/book.html', $book);

        // copy book images
        $this->app->get('filesystem')->mirror(
            $this->app['publishing.dir.contents'].'/images',
            $this->app['publishing.dir.output'].'/images'
        );
    }
}