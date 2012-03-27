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

class PdfPublisher extends BasePublisher
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
        // implode all the contents to create the whole book
        $book = $this->app->render('book.twig', array(
            'items' => $this->app['publishing.items']
        ));
        $temp = tempnam(sys_get_temp_dir(), 'easybook_');
        fputs(fopen($temp, 'w+'), $book);

        // use PrinceXML to transform the HTML book into a PDF book
        $prince = $this->app->get('prince');
        $prince->setBaseURL($this->app['publishing.dir.contents'].'/images');

        // Prepare and add stylesheets before PDF conversion
        if ($this->app->edition('include_styles')) {
            $defaultStyles = tempnam(sys_get_temp_dir(), 'easybook_style_');
            $this->app->renderThemeTemplate(
                'style.css.twig',
                array('resources_dir' => $this->app['app.dir.resources'].'/'),
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
        $prince->convert_file_to_file($temp, $this->app['publishing.dir.output'].'/book.pdf', &$errorMessages);

        // show PDF conversion errors
        if (count($errorMessages) > 0) {
            foreach($errorMessages as $message) {
                echo $message[0].': '.$message[2].' ('.$message[1].')'."\n";
            }
        }
    }
}