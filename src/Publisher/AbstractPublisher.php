<?php declare(strict_types=1);

namespace Easybook\Publisher;

use Easybook\Book\Content;
use Easybook\Book\Edition;
use Easybook\Book\Item;
use Easybook\Book\ItemConfig;
use Easybook\Book\Provider\BookProvider;
use Easybook\Contract\Publisher\PublisherInterface;
use Easybook\Events\EasybookEvents;
use Easybook\Events\ItemAwareEvent;
use Easybook\Templating\Renderer;
use Easybook\Util\Slugger;
use Michelf\MarkdownExtra;
use Nette\Utils\FileSystem as NetteFileSystem;
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
     * @var Item[]
     */
    protected $publishingItems = [];

    /**
     * @var string
     */
    protected $bookContentsDir;

    /**
     * @var BookProvider
     */
    private $bookProvider;

    /**
     * @var MarkdownExtra
     */
    private $markdownExtra;

    /**
     * @required
     */
    public function setRequiredDependencies(
        EventDispatcherInterface $eventDispatcher,
        Filesystem $filesystem,
        Renderer $renderer,
        Slugger $slugger,
        MarkdownExtra $markdownExtra,
        string $bookContentsDir,
        BookProvider $bookProvider
    ): void {
        $this->eventDispatcher = $eventDispatcher;
        $this->filesystem = $filesystem;
        $this->renderer = $renderer;
        $this->slugger = $slugger;
        $this->markdownExtra = $markdownExtra;
        $this->bookContentsDir = $bookContentsDir;
        $this->bookProvider = $bookProvider;
    }

    /**
     * It controls the book publishing workflow for this particular publisher.
     */
    public function publishBook(string $outputDirectory): string
    {
        $this->loadContents();
        $this->parseContents();
        $this->assembleBook($outputDirectory);
    }

    /**
     * It parses the original (Markdown) book contents and transforms  them into the output (HTML) format.
     * It also notifies several events to allow plugins modify the content before and/or after the transformation.
     */
    public function parseContents(): void
    {
        foreach ($this->publishingItems as $item) {
            $itemAwareEvent = new ItemAwareEvent($item, new Edition($this->getFormat()));
            $this->eventDispatcher->dispatch(EasybookEvents::PRE_PARSE, $itemAwareEvent);

            $item->changeContent($this->markdownExtra->transform($item->getOriginal()));

            $this->eventDispatcher->dispatch(EasybookEvents::POST_PARSE, $itemAwareEvent);
        }
    }

    /**
     * It loads the original content of each of the book's items. If the item
     * doesn't define its own content (such as the table of contents or the
     * cover) it loads the default content (if defined).
     */
    protected function loadContents(): void
    {
        foreach ($this->bookProvider->provide()->getContents() as $content) {
            $item = $this->initializeItem($content);

            $itemConfig = $item->getItemConfig();

            if ($itemConfig->getContent()) {
                // the element defines its own content file (usually chapters and appendices)
                $item->changeOriginal($this->loadItemContent($itemConfig->getContent(), $itemConfig->getElement()));
            } else {
                // the element doesn't define its own content file (try to load the default
                // content for this item type, if any)
                $item->changeOriginal($this->loadDefaultItemContent($itemConfig->getElement()));
            }

            $this->publishingItems[] = $item;
        }
    }

    abstract protected function assembleBook(string $outputDirectory): string;

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
        return NetteFileSystem::read($contentFilePath);
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
     */
    private function initializeItem(Content $content): Item
    {
        $itemConfig = new ItemConfig(
            // the name of this item contents file (it's a relative path from book's `Contents/`)
            (string) $content->getContent(),
            // the type of this content (`chapter`, `appendix`, `toc`, `license`, ...)
            $content->getElement(),
            // the number/letter of the content (useful for `chapter`, `part` and `appendix`)
            $content->getNumber(),
            // the title of the content defined in `config.yml` (usually only `part` defines it)
            $content->getTitle()
        );

        return new Item(
            $itemConfig,
            // original content as written by book author (Markdown usually)
            '',
            // the label of this item ('Chapter XX', 'Appendix XX', ...)
            '',
            // the title of the item without any label ('Lorem ipsum dolor')
            (string) $itemConfig->getTitle(),
            // the table of contents of this item
            []
        );
    }

    /**
     * It prepares the book images by copying them into the appropriate
     * temporary directory. It also prepares an array with all the images
     * data needed later to generate the full ebook contents manifest.
     *
     * @param string $targetDir The directory where the images are copied.
     *
     * @return array Images data needed to create the book manifest.
     *
     * @throws \RuntimeException If the $targetDir doesn't exist.
     */
    protected function prepareBookImages($targetDir)
    {
        if (!file_exists($targetDir)) {
            throw new \RuntimeException(
                sprintf(
                    " ERROR: Books images couldn't be copied because \n"
                    . " the given '%s' \n"
                    . " directory doesn't exist.",
                    $targetDir
                )
            );
        }
        $imagesDir = $this->app['publishing.dir.contents'] . '/images';
        $imagesData = array();
        if (file_exists($imagesDir)) {
            $images = $this->app['finder']->files()->in($imagesDir);
            $i = 1;
            foreach ($images as $image) {
                $this->app['filesystem']->copy(
                    $image->getPathName(),
                    $targetDir . '/' . $image->getFileName()
                );
                $imagesData[] = array(
                    'id'        => 'figure-' . $i++,
                    'filePath'  => 'images/' . $image->getFileName(),
                    'mediaType' => 'image/' . pathinfo($image->getFilename(), PATHINFO_EXTENSION),
                );
            }
        }
        return $imagesData;
    }
}
