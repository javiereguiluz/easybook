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
use Easybook\Events\ParseEvent;

/**
 * It publishes the book as a PDF file. All the internal links are transformed
 * into clickable cross-section book links. These links even display automatically
 * the page number where they point into, so no information is lost when printing
 * the book.
 */
class PdfPublisher extends BasePublisher
{
    public function parseContents()
    {
        $parsedItems = array();

        foreach ($this->app->get('publishing.items') as $item) {
            $this->app->set('publishing.active_item', $item);

            // filter the original item content before parsing it
            $event = new ParseEvent($this->app);
            $this->app->dispatch(Events::PRE_PARSE, $event);

            // get again 'item' object because PRE_PARSE event can modify it
            $item = $this->app->get('publishing.active_item');

            $item['content'] = $this->app->get('parser')->transform($item['original']);
            $item['toc']     = $this->app->get('publishing.active_item.toc');

            $this->app->set('publishing.active_item', $item);

            $event = new ParseEvent($this->app);
            $this->app->dispatch(Events::POST_PARSE, $event);

            // get again 'item' object because POST_PARSE event can modify it
            $parsedItems[] = $this->app->get('publishing.active_item');
        }

        $this->app->set('publishing.items', $parsedItems);
    }

    public function decorateContents()
    {
        $decoratedItems = array();

        foreach ($this->app->get('publishing.items') as $item) {
            $this->app->set('publishing.active_item', $item);

            // filter the original item content before decorating it
            $event = new BaseEvent($this->app);
            $this->app->dispatch(Events::PRE_DECORATE, $event);

            // get again 'item' object because PRE_DECORATE event can modify it
            $item = $this->app->get('publishing.active_item');
            $item['content'] = $this->app->render(
                $item['config']['element'].'.twig',
                array('item' => $item)
            );

            $this->app->set('publishing.active_item', $item);

            $event = new BaseEvent($this->app);
            $this->app->dispatch(Events::POST_DECORATE, $event);

            // get again 'item' object because POST_DECORATE event can modify it
            $decoratedItems[] = $this->app->get('publishing.active_item');
        }

        $this->app->set('publishing.items', $decoratedItems);
    }

    public function assembleBook()
    {
        $tmpDir = $this->app->get('app.dir.cache').'/'.uniqid('easybook_pdf_');
        $this->app->get('filesystem')->mkdir($tmpDir);

        // implode all the contents to create the whole book
        $htmlBookFilePath = $tmpDir.'/book.html';
        $this->app->render(
            'book.twig',
            array('items' => $this->app->get('publishing.items')),
            $htmlBookFilePath
        );

        // use PrinceXML to transform the HTML book into a PDF book
        $prince = $this->app->get('prince');
        $prince->setBaseURL($this->app->get('publishing.dir.contents').'/images');

        // Prepare and add stylesheets before PDF conversion
        if ($this->app->edition('include_styles')) {
            $defaultStyles = $tmpDir.'/default_styles.css';
            $this->app->render(
                '@theme/style.css.twig',
                array('resources_dir' => $this->app->get('app.dir.resources').'/'),
                $defaultStyles
            );

            $prince->addStyleSheet($defaultStyles);
        }

        // TODO: custom book styles could also be defined with Twig
        $customCss = $this->app->getCustomTemplate('style.css');
        if (file_exists($customCss)) {
            $prince->addStyleSheet($customCss);
        }

        // TODO: the name of the book file (book.pdf) must be configurable
        $errorMessages = array();
        $prince->convert_file_to_file($htmlBookFilePath, $this->app->get('publishing.dir.output').'/book.pdf', $errorMessages);

        // display PDF conversion errors
        if (count($errorMessages) > 0) {
            $this->app->get('console.output')->writeln("\n PrinceXML errors and warnings");
            $this->app->get('console.output')->writeln(" -----------------------------\n");
            foreach ($errorMessages as $message) {
                $this->app->get('console.output')->writeln(
                    '   ['.strtoupper($message[0]).'] '.ucfirst($message[2]).' ('.$message[1].')'
                );
            }
            $this->app->get('console.output')->writeln("\n");
        }
    }
}
