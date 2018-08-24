<?php declare(strict_types=1);

namespace Easybook\Publishers;

use Easybook\Book\Item;
use Easybook\Book\ItemConfig;
use Easybook\Events\EasybookEvents as Events;
use Easybook\Events\ItemAwareEvent;
use Easybook\Parsers\ParserInterface;
use Easybook\Templating\Renderer;
use Easybook\Util\Slugger;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use Twig_Error_Loader;

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
     * @var Renderer
     */
    protected $renderer;

    /**
     * @var Slugger
     */
    protected $slugger;

    /**
     * @var mixed[]
     */
    protected $publishingItems = [];

    /**
     * @var string
     */
    protected $bookContentsDir;

    /**
     * @var string
     */
    private $publishingDirOutput;

    /**
     * @var ParserInterface
     */
    private $parser;

    /**
     * @required
     */
    public function setRequiredDependencies(
        EventDispatcherInterface $eventDispatcher,
        Filesystem $filesystem,
        Renderer $renderer,
        Slugger $slugger,
        ParserInterface $parser,
        string $publishingDirOutput,
        string $bookContentsDir
    ): void {
        $this->eventDispatcher = $eventDispatcher;
        $this->filesystem = $filesystem;
        $this->renderer = $renderer;
        $this->slugger = $slugger;
        $this->parser = $parser;
        $this->publishingDirOutput = $publishingDirOutput;
        $this->bookContentsDir = $bookContentsDir;
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
        foreach ($this->publishingItems as $key => $item) {
            $parseEvent = new ItemAwareEvent($item);
            $this->eventDispatcher->dispatch(Events::PRE_PARSE, $parseEvent);

            $parseEvent->changeItemProperty(
                'content',
                $this->parser->transform($parseEvent->getItemProperty('original'))
            );

            $this->eventDispatcher->dispatch(Events::POST_PARSE, $parseEvent);
            $this->publishingItems[$key] = $parseEvent->getItem();
        }
    }

    /**
     * Decorates each book item with the appropriate Twig template.
     */
    public function decorateContents(): void
    {
        foreach ($this->publishingItems as $key => $item) {
            $parseEvent = new ItemAwareEvent($item);

            $this->eventDispatcher->dispatch(Events::PRE_DECORATE, $parseEvent);

            $parseEvent->changeItemProperty(
                'content',
                $this->renderer->render($item['config']['element'] . '.twig', ['item' => $item])
            );

            $this->eventDispatcher->dispatch(Events::POST_DECORATE, $parseEvent);

            $this->publishingItems[$key] = $parseEvent->getItem();
        }
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

            $this->publishingItems[] = $item;
        }
    }

    abstract protected function assembleBook(): void;

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
     */
    private function loadItemContent(string $contentFileName, string $itemType): string
    {
        $contentFilePath = $this->bookContentsDir . '/' . $contentFileName;

        // check that content file exists and is readable
        if (! is_readable($contentFilePath)) {
            throw new RuntimeException(sprintf(
                "The '%s' content associated with '%s' element doesn't exist\n"
                    . "or is not readable.\n\n"
                    . "Check that '%s'\n"
                    . 'file exists and check its permissions.',
                $contentFileName,
                $itemType,
                realpath($this->bookContentsDir) . '/' . $contentFileName
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
     * @param mixed[] $itemConfig
     */
    private function initializeItem(array $itemConfig): Item
    {
        $itemConfig = new ItemConfig(
            // the name of this item contents file (it's a relative path from book's `Contents/`)
            $itemConfig['content'] ?? '',
            // the type of this content (`chapter`, `appendix`, `toc`, `license`, ...)
            $itemConfig['element'] ?? '',
            // the format in which contents are written ('md' for Markdown)
            $itemConfig['format'] ?? '',
            // the number/letter of the content (useful for `chapter`, `part` and `appendix`)
            $itemConfig['number'] ?? '',
            // the title of the content defined in `config.yml` (usually only `part` defines it)
            $itemConfig['title'] ?? ''
        );

        return new Item(
            $itemConfig,
            // original content as written by book author (Markdown usually)
            '',
            // the label of this item ('Chapter XX', 'Appendix XX', ...)
            '',
            // the title of the item without any label ('Lorem ipsum dolor')
            $item['config']['title'] ?? '',
            // the table of contents of this item
            []
        );
    }
}
