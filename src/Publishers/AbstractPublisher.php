<?php declare(strict_types=1);

namespace Easybook\Publishers;

use Easybook\Events\AbstractEvent;
use Easybook\Events\EasybookEvents as Events;
use Easybook\Events\ParseEvent;
use Easybook\Templating\Renderer;
use Easybook\Util\Slugger;
use RuntimeException;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use Twig_Error_Loader;
use Twig_Error_Syntax;

abstract class AbstractPublisher implements PublisherInterface
{
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var SymfonyStyle
     */
    private $symfonyStyle;

    /**
     * @var Renderer
     */
    protected $renderer;

    /**
     * @var string
     */
    private $publishingDirOutput;

    /**
     * @var Slugger
     */
    private $slugger;

    /**
     * @required
     */
    public function setRequiredDependencies(
        EventDispatcherInterface $eventDispatcher,
        Filesystem $filesystem,
        SymfonyStyle $symfonyStyle,
        Renderer $renderer,
        Slugger $slugger,
        string $publishingDirOutput
    )
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->filesystem = $filesystem;
        $this->symfonyStyle = $symfonyStyle;
        $this->renderer = $renderer;
        $this->slugger = $slugger;
        $this->publishingDirOutput = $publishingDirOutput;
    }

    /**
     * It controls the book publishing workflow for this particular publisher.
     */
    public function publishBook(): void
    {
        $this->loadContents();
        $this->parseContents();
        $this->prepareOutputDir();
        $this->decorateContents();
        $this->assembleBook();
    }

    /**
     * It parses the original (Markdown) book contents and transforms
     * them into the output (HTML) format. It also notifies several
     * events to allow plugins modify the content before and/or after
     * the transformation.
     */
    public function parseContents(): void
    {
        $parsedItems = [];

        foreach ($this->app['publishing.items'] as $item) {
            $this->app['publishing.active_item'] = $item;

            // filter the original item content before parsing it
            $this->eventDispatcher->dispatch(Events::PRE_PARSE, new ParseEvent());

            // get again 'item' object because PRE_PARSE event can modify it
            $item = $this->app['publishing.active_item'];

            // when publishing a book as a website, two pages in different sections
            // can use the same slugs without resulting in collisions.
            // TODO: this method should be smarter: if chunk_level = 1, it's safe
            // to delete all previous slugs, but if chunk_level = 2, we should only
            // remove the generated slugs for each section
            if ($this->app->edition('format') === 'html_chunked') {
                $this->slugger->resetGeneratedSlugs();
            }

            $item['content'] = $this->app['parser']->transform($item['original']);
            $item['toc'] = $this->app['publishing.active_item.toc'];

            $this->app['publishing.active_item'] = $item;

            $this->eventDispatcher->dispatch(Events::POST_PARSE, new ParseEvent());

            // get again 'item' object because POST_PARSE event can modify it
            $parsedItems[] = $this->app['publishing.active_item'];
        }

        $this->app['publishing.items'] = $parsedItems;
    }

    /**
     * Decorates each book item with the appropriate Twig template.
     */
    public function decorateContents(): void
    {
        $decoratedItems = [];

        foreach ($this->app['publishing.items'] as $item) {
            $this->app['publishing.active_item'] = $item;

            // filter the original item content before decorating it
            $this->eventDispatcher->dispatch(Events::PRE_DECORATE, new Event());

            // get again 'item' object because PRE_DECORATE event can modify it
            $item = $this->app['publishing.active_item'];

            $item['content'] = $this->renderer->render($item['config']['element'] . '.twig', ['item' => $item]);

            $this->app['publishing.active_item'] = $item;

            $this->eventDispatcher->dispatch(Events::POST_DECORATE, new Event());

            // get again 'item' object because POST_DECORATE event can modify it
            $decoratedItems[] = $this->app['publishing.active_item'];
        }

        $this->app['publishing.items'] = $decoratedItems;
    }

    /**
     * It loads the original content of each of the book's items. If the item
     * doesn't define its own content (such as the table of contents or the
     * cover) it loads the default content (if defined).
     */
    protected function loadContents(): void
    {
        foreach ($this->app->book('contents') as $itemConfig) {
            $item = $this->initializeItem($itemConfig);

            // for now, easybook only supports Markdown format
            $item['config']['format'] = 'md';

            if (isset($itemConfig['content'])) {
                // the element defines its own content file (usually chapters and appendices)
                $item['original'] = $this->loadItemContent($itemConfig['content'], $itemConfig['element']);
            } else {
                // the element doesn't define its own content file (try to load the default
                // content for this item type, if any)
                $item['original'] = $this->loadDefaultItemContent($itemConfig['element']);
            }

            $this->app->append('publishing.items', $item);
        }
    }

    private function prepareOutputDir(): void
    {
        $this->filesystem->mkdir($this->publishingDirOutput);
    }

    /**
     * It loads the contents of the given content file name. Most of the time
     * this means returning the Markdown content stored in that file. Anyway,
     * the contents can also be defined with Twig and Markdown simultaneously.
     * In those cases, the content is parsed as a Twig template before returning
     * the resulting Markdown content.
     *
     * @param string $contentFileName The name of the file that stores the item content
     * @param string $itemType        The type of the item (e.g. 'chapter')
     *
     * @return string The content of the item (currently, this content is always in
     *                Markdown format)
     *
     * @throws \RuntimeException If the content file doesn't exist or is not readable
     */
    private function loadItemContent(string $contentFileName, string $itemType): string
    {
        $contentFilePath = $this->app['publishing.dir.contents'] . '/' . $contentFileName;

        // check that content file exists and is readable
        if (! is_readable($contentFilePath)) {
            throw new RuntimeException(sprintf(
                "The '%s' content associated with '%s' element doesn't exist\n"
                    . "or is not readable.\n\n"
                    . "Check that '%s'\n"
                    . 'file exists and check its permissions.',
                $contentFileName,
                $itemType,
                realpath($this->app['publishing.dir.contents']) . '/' . $contentFileName
            ));
        }

        // if the element content uses Twig (such as *.md.twig), parse
        // the Twig template before parsing the Markdown contents
        if (substr($contentFilePath, -5) === '.twig') {
            return $this->renderer->render(file_get_contents($contentFilePath));
        }

        // if the element content only uses Markdown (*.md), load
        // directly its contents in the $item['original'] property
        return file_get_contents($contentFilePath);
    }

    /**
     * Tries to load the default content defined by easybook for this item type.
     *
     * @param string $itemType The type of item (e.g. 'cover', 'license', 'title')
     */
    private function loadDefaultItemContent(string $itemType): string
    {
        $contentFileName = $itemType . '.md.twig';

        try {
            return $this->renderer->render('@content/' . $contentFileName);
        } catch (Twig_Error_Loader $e) {
            // if Twig throws a Twig_Error_Loader exception,
            // there is no default content
            return '';
        }
    }

    /**
     * It initializes an array with the configuration options and data of each
     * book element (a chapter, an appendix, the table of contens, etc.).
     *
     * @param array $itemConfig The configuration options set in the config.yml
     *                          file for this item.
     *
     * @return array An array with all the configuration options and data for the item
     */
    private function initializeItem(array $itemConfig): array
    {
        $item = [];

        $item['config'] = array_merge([
            // the name of this item contents file (it's a relative path from book's `Contents/`)
            'content' => '',
            // the type of this content (`chapter`, `appendix`, `toc`, `license`, ...)
            'element' => '',
            // the format in which contents are written ('md' for Markdown)
            'format' => '',
            // the number/letter of the content (useful for `chapter`, `part` and `appendix`)
            'number' => '',
            // the title of the content defined in `config.yml` (usually only `part` defines it)
            'title' => '',
        ], $itemConfig);

        $item['original'] = '';      // original content as written by book author (Markdown usually)
        $item['content'] = '';      // transformed content of the item (HTML usually)
        $item['label'] = '';      // the label of this item ('Chapter XX', 'Appendix XX', ...)
        $item['title'] = '';      // the title of the item without any label ('Lorem ipsum dolor')
        $item['slug'] = '';      // the slug of the title
        $item['toc'] = []; // the table of contents of this item

        if (! empty($item['config']['title'])) {
            $item['title'] = $item['config']['title'];
            $item['slug'] = $this->app->slugify($item['title']);
        }

        return $item;
    }

    abstract protected function assembleBook();
}
