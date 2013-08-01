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

use Easybook\DependencyInjection\Application;
use Easybook\Events\EasybookEvents as Events;
use Easybook\Events\ParseEvent;

class BasePublisher implements PublisherInterface
{
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function checkIfThisPublisherIsSupported()
    {
        return true;
    }

    /**
     * It controls the book publishing workflow for this particular publisher.
     */
    public function publishBook()
    {
        $this->loadContents();
        $this->parseContents();
        $this->prepareOutputDir();
        $this->decorateContents();
        $this->assembleBook();
    }

    /**
     * It loads the original content of each of the book's items. If the item
     * doesn't define its own content (such as the table of contents or the
     * cover) it loads the default content (if defined).
     */
    protected function loadContents()
    {
        // TODO: extensibility -> editions can redefine book contents (to remove or reorder items)
        foreach ($this->app->book('contents') as $itemConfig) {
            $item = $this->initializeItem($itemConfig);

            // for now, easybook only supports Markdown format
            $item['config']['format'] = 'md';

            // if the element defines its own content file (usually chapters and appendices)
            if (array_key_exists('content', $itemConfig)) {
                $contentFile = $this->app['publishing.dir.contents'].'/'.$itemConfig['content'];

                // check that content file exists and is readable
                if (!is_readable($contentFile)) {
                    throw new \RuntimeException(sprintf(
                        "The '%s' content associated with '%s' element doesn't exist\n"
                        ."or is not readable.\n\n"
                        ."Check that '%s'\n"
                        ."file exists and check its permissions.",
                        $itemConfig['content'],
                        $itemConfig['element'],
                        realpath($this->app['publishing.dir.contents']).'/'.$itemConfig['content']
                    ));
                }

                // TODO: document the following change:
                // contents can now be defined with Twig and a markup language

                // if the element content uses Twig (such as *.md.twig), parse
                // the Twig template before parsing the Markdown contents
                if ('.twig' == substr($contentFile, -5)) {
                    try {
                        $item['original'] = $this->app->renderString(file_get_contents($contentFile));
                    } catch (\Twig_Error_Syntax $e) {
                        // if there is a Twig parsing error, notify the user but don't
                        // stop the book publication
                        $this->app['console.output']->writeln(sprintf(
                            " [WARNING] There was an error while parsing the \"%s\" file\n",
                            $contentFile
                        ));
                    }
                // if the element content only uses Markdown (*.md), load
                // directly its contents in the $item 'original' property
                } else {
                    $item['original'] = file_get_contents($contentFile);
                }
            } else {
                // look for a default content defined by easybook for this element
                // e.g. `cover.md.twig`, `license.md.twig`, `title.md.twig`
                try {
                    $contentFile = $itemConfig['element'].'.md.twig';
                    $item['original'] = $this->app->render('@content/'.$contentFile);
                }
                // if Twig throws a Twig_Error_Loader exception, there is no default content
                catch (\Twig_Error_Loader $e) {
                    $item['original'] = '';
                }
            }

            $this->app->append('publishing.items', $item);
        }
    }

    /**
     * It parses the original (Markdown) book contents and transforms
     * them into the output (HTML) format. It also notifies several
     * events to allow plugins modify the content before and/or after
     * the transformation.
     */
    public function parseContents()
    {
        $parsedItems = array();

        foreach ($this->app['publishing.items'] as $item) {
            $this->app['publishing.active_item'] = $item;

            // filter the original item content before parsing it
            $event = new ParseEvent($this->app);
            $this->app->dispatch(Events::PRE_PARSE, $event);

            // get again 'item' object because PRE_PARSE event can modify it
            $item = $this->app['publishing.active_item'];

            $item['content'] = $this->app['parser']->transform($item['original']);
            $item['toc']     = $this->app['publishing.active_item.toc'];

            $this->app['publishing.active_item'] = $item;

            $event = new ParseEvent($this->app);
            $this->app->dispatch(Events::POST_PARSE, $event);

            // get again 'item' object because POST_PARSE event can modify it
            $parsedItems[] = $this->app['publishing.active_item'];
        }

        $this->app['publishing.items'] = $parsedItems;
    }

    /**
     * It creates the directory where the final book contents will be copied.
     */
    protected function prepareOutputDir()
    {
        $bookOutputDir = $this->app['publishing.dir.output']
            ?: $this->app['publishing.dir.book'].'/Output/'.$this->app['publishing.edition'];

        if (!file_exists($bookOutputDir)) {
            $this->app['filesystem']->mkdir($bookOutputDir);
        }

        $this->app['publishing.dir.output'] = $bookOutputDir;
    }

    /**
     * It initializes an array with the configuration options and data of each
     * book element (a chapter, an appendix, the table of contens, etc.)
     *
     * @param  array $itemConfig The configuration options set in the config.yml
     *                           file for this item.
     *
     * @return array An array with all the configuration options and data for the item
     */
    private function initializeItem($itemConfig)
    {
        $item = array();

        $item['config'] = array_merge(array(
            // the name of this item contents file (it's a relative path from book's `Contents/`)
            'content' => '',
            // the type of this content (`chapter`, `appendix`, `toc`, `license`, ...)
            'element' => '',
            // the format in which contents are written ('md' for Markdown)
            'format'  => '',
            // the number/letter of the content (useful for `chapter`, `part` and `appendix`)
            'number'  => '',
            // the title of the content defined in `config.yml` (usually only `part` defines it)
            'title'   => '',
        ), $itemConfig);

        $item['original'] = '';      // original content as written by book author (Markdown usually)
        $item['content']  = '';      // transformed content of the item (HTML usually)
        $item['label']    = '';      // the label of this item ('Chapter XX', 'Appendix XX', ...)
        $item['title']    = '';      // the title of the item without any label ('Lorem ipsum dolor')
        $item['slug']     = '';      // the slug of the title
        $item['toc']      = array(); // the table of contents of this item

        if (!empty($item['config']['title'])) {
            $item['title'] = $item['config']['title'];
            $item['slug']  = $this->app->slugify($item['title']);
        }

        return $item;
    }
}
