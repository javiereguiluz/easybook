<?php declare(strict_types=1);

namespace Easybook\Publisher;

use Easybook\Book\Item;
use Easybook\Book\Provider\BookProvider;
use Easybook\Util\Toolkit;
use RuntimeException;
use Symfony\Component\Finder\Finder;
use Twig_Error_Loader;

/**
 * It publishes the book as an EPUB file. All the internal links are transformed
 * into clickable cross-section book links.
 */
final class Epub2Publisher extends AbstractPublisher
{
    /**
     * @var string
     */
    public const NAME = 'epub';

    /**
     * - 'toc' content type usually makes no sense in epub books (see below)
     * - 'cover' is a very special content for epub books
     *
     * @var string[]
     */
    private $excludedElements = ['cover', 'lot', 'lof', 'toc'];

    /**
     * @var Toolkit
     */
    private $toolkit;

    /**
     * @var Finder
     */
    private $finder;

    /**
     * @var BookProvider
     */
    private $bookProvider;

    /**
     * @var string
     */
    private $resourcesDir;

    /**
     * @var string
     */
    private $bookTemporaryCacheDir;

    /**
     * @var string|null
     */
    private $coverImage;

    public function __construct(
        Toolkit $toolkit,
        Finder $finder,
        BookProvider $bookProvider,
        string $resourcesDir,
        string $bookTemporaryCacheDir,
        ?string $coverImage
    ) {
        $this->toolkit = $toolkit;
        $this->finder = $finder;
        $this->bookProvider = $bookProvider;
        $this->resourcesDir = $resourcesDir;
        $this->bookTemporaryCacheDir = $bookTemporaryCacheDir;
        $this->coverImage = $coverImage;
    }

    public function loadContents(): void
    {
        $book = $this->bookProvider->provide();

        foreach ($book->getContents() as $content) {
            // strip excluded elements before loading book contents
            if (! in_array($content->getElement(), $this->excludedElements, true)) {
                $book->removeContent($content);
            }
        }

        parent::loadContents();
    }

    public function assembleBook(string $outputDirectory): string
    {
        $bookTmpDir = $this->prepareBookTemporaryDirectory();

        // generate easybook CSS file
        $this->renderer->renderToFile(
            '@theme/style.css.twig',
            ['resources_dir' => '..'],
            $bookTmpDir . '/book/OEBPS/css/easybook.css'
        );

        $this->normalizePageNames($this->publishingItems);

        // generate HTML page for every book item
        foreach ($this->publishingItems as $item) {
            $renderedTemplatePath = $bookTmpDir . '/book/OEBPS/' . $item->getPageName() . '.html';

            // try first to render the specific template for each content
            // type, if it exists (e.g. toc.twig, chapter.twig, etc.) and
            // use chunk.twig as the fallback template
            try {
                $templateName = $item->getConfigElement() . '.twig';

                $this->renderer->renderToFile($templateName, ['item' => $item], $renderedTemplatePath);
            } catch (Twig_Error_Loader $e) {
                $this->renderer->renderToFile('chunk.twig', ['item' => $item], $renderedTemplatePath);
            }
        }

        $bookImages = $this->prepareBookImages($bookTmpDir . '/book/OEBPS/images');
        $bookCover = $this->prepareBookCoverImage($bookTmpDir . '/book/OEBPS/images');
        $bookFonts = $this->prepareBookFonts($bookTmpDir . '/book/OEBPS/fonts');

        // generate the book cover page
        $this->renderer->renderToFile(
            'cover.twig',
            ['customCoverImage' => $bookCover],
            $bookTmpDir . '/book/OEBPS/titlepage.html'
        );

        // generate the OPF file (the ebook manifest)
        $this->renderer->renderToFile(
            'content.opf.twig',
            [
                'cover' => $bookCover,
                'fonts' => $bookFonts,
                'images' => $bookImages,
                'items' => $this->publishingItems,
            ],
            $bookTmpDir . '/book/OEBPS/content.opf'
        );

        // generate the NCX file (the table of contents)
        $this->renderer->renderToFile(
            'toc.ncx.twig',
            ['items' => $this->publishingItems],
            $bookTmpDir . '/book/OEBPS/toc.ncx'
        );

        // generate container.xml and mimetype files
        $this->renderer->renderToFile('container.xml.twig', [], $bookTmpDir . '/book/META-INF/container.xml');
        $this->renderer->renderToFile('mimetype.twig', [], $bookTmpDir . '/book/mimetype');

        $this->fixInternalLinks($bookTmpDir . '/book/OEBPS');

        // compress book contents as ZIP file and rename to .epub
        $this->zipBookContents($bookTmpDir . '/book', $bookTmpDir . '/book.zip');
        $this->filesystem->copy($bookTmpDir . '/book.zip', $outputDirectory . '/book.epub', true);

        // remove temp directory used to build the book
        $this->filesystem->remove($bookTmpDir);

        return $outputDirectory . '/book.epub';
    }

    public function getFormat(): string
    {
        return self::NAME;
    }

    /**
     * @param $TYPE$[] $$$END$
     */
    public function setExcludedElements(array $excludedElements): void
    {
    }

    /**
     * Prepares the temporary directory where the book contents are generated
     * before packing them into the resulting EPUB file. It also creates the
     * full directory structure required for EPUB books.
     *
     * @return string The absolute path of the directory created.
     */
    private function prepareBookTemporaryDirectory(): string
    {
        $bookDir = $this->bookTemporaryCacheDir . uniqid();

        $this->filesystem->mkdir([
            $bookDir,
            $bookDir . '/book',
            $bookDir . '/book/META-INF',
            $bookDir . '/book/OEBPS',
            $bookDir . '/book/OEBPS/css',
            $bookDir . '/book/OEBPS/images',
            $bookDir . '/book/OEBPS/fonts',
        ]);

        return $bookDir;
    }

    /**
     * It prepares the book images by copying them into the appropriate
     * temporary directory. It also prepares an array with all the images
     * data needed later to generate the full ebook contents manifest.
     *
     * @param string $targetDir The directory where the images are copied.
     *
     * @return mixed[]
     */
    private function prepareBookImages(string $targetDir): array
    {
        $this->ensureDirectoryExists($targetDir, 'images');

        $imagesDir = $this->bookContentsDir . '/images';
        if (! file_exists($imagesDir)) {
            return [];
        }

        $imagesData = [];

        $images = $this->finder->files()
            ->in($imagesDir)
            ->getIterator();

        $i = 1;
        foreach ($images as $image) {
            $this->filesystem->copy($image->getPathName(), $targetDir . '/' . $image->getFileName());

            // @todo object?
            $imagesData[] = [
                'id' => 'figure-' . ++$i,
                'filePath' => 'images/' . $image->getFileName(),
                'mediaType' => 'image/' . pathinfo($image->getFilename(), PATHINFO_EXTENSION),
            ];
        }

        return $imagesData;
    }

    /**
     * It prepares the book cover image if defined
     *
     * @return mixed[]|null
     */
    private function prepareBookCoverImage(string $targetDir): ?array
    {
        if (! $this->coverImage) {
            return null;
        }

        [$width, $height, $type] = getimagesize($this->coverImage);

        // @todo object?
        $cover = [
            'height' => $height,
            'width' => $width,
            'filePath' => 'images/' . basename($this->coverImage),
            'mediaType' => image_type_to_mime_type($type),
        ];

        $this->filesystem->copy($this->coverImage, $targetDir . DIRECTORY_SEPARATOR . basename($this->coverImage));

        return $cover;
    }

    /**
     * It prepares the book fonts by copying them into the appropriate
     * temporary directory. It also prepares an array with all the font
     * data needed later to generate the full ebook contents manifest.
     *
     * For now, epub books only include the Inconsolata font to display
     * their code listings.
     *
     * @return mixed[]
     */
    private function prepareBookFonts(string $targetDir): array
    {
        $this->ensureDirectoryExists($targetDir, 'fonts');

        $fontsDir = $this->resourcesDir . '/Fonts/Inconsolata';
        if (! file_exists($fontsDir)) {
            return [];
        }

        $fontsData = [];

        $fonts = $this->finder->files()
            ->name('*.ttf')
            ->in($fontsDir)
            ->getIterator();

        $i = 0;
        foreach ($fonts as $font) {
            $this->filesystem->copy($font->getPathName(), $targetDir . DIRECTORY_SEPARATOR . $font->getFileName());

            // @todo object
            $fontsData[] = [
                'id' => 'font-' . ++$i,
                'filePath' => 'fonts/' . $font->getFileName(),
                'mediaType' => finfo_file(finfo_open(FILEINFO_MIME_TYPE), $font->getPathName()),
            ];
        }

        return $fontsData;
    }

    /**
     * The generated HTML pages aren't named after the items' original slugs
     * (e.g. introduction-to-lorem-ipsum.html) but using their content types
     * and numbers (e.g. chapter-1.html).
     *
     * This method creates a new property for each item called 'page_name' which
     * stores the normalized page name that should have this chunk.
     *
     * @param Item[] $items
     */
    private function normalizePageNames(array $items): void
    {
        foreach ($items as $item) {
            $itemPageName = $item->getConfigNumber()
                ? $item->getConfigElement() . ' ' . $item->getConfigNumber()
                : $item->getConfigElement();

            $item->setPageName($this->slugger->slugifyUniquely($itemPageName));
        }
    }

    /*
     * It creates the ZIP file of the .epub book contents.
     *
     * According to the "International Digital Publishing Forum" validator,
     * a valid ePub file must have "an uncompressed Mimetype entry at the first
     * element of the compressed archive".
     *
     * See: https://github.com/php/php-src/commit/3a55ea02
     */
    private function zipBookContents(string $directory, string $zipFile): void
    {
        $this->toolkit->zip($directory, $zipFile);
    }

    /**
     * If fixes the internal links of the book (the links that point to chapters
     * and sections of the book).
     *
     * The author of the book always uses relative links, such as:
     *   see <a href="#new-content-types">this section</a> for more information
     *
     * In order to work, the relative URIs must be replaced by absolute URIs:
     *   see <a href="chapter3/page-slug.html#new-content-types">this section</a>
     *
     * Unlike books published as websites, the absolute URIs of the ePub books
     * cannot start with './' or '../' In other words, ./chapter.html and
     * ./chapter.html#section-slug are wrong and chapter.html or
     * chapter.html#section-slug are right.
     *
     * @param string $chunksDir The directory where the book's HTML page/chunks
     *                          are stored
     */
    private function fixInternalLinks(string $chunksDir): void
    {
        $generatedChunks = $this->finder->files()
            ->name('*.html')
            ->in($chunksDir)
            ->getIterator();

        // maps the original internal links (e.g. #new-content-types)
        // with the correct absolute URL needed for a website
        // (e.g. chapter-3/advanced-features.html#new-content-types
        $internalLinkMapper = [];

        //look for the ID of every book section
        foreach ($generatedChunks as $chunk) {
            $htmlContent = file_get_contents($chunk->getPathname());

            $matches = [];
            $numHeadings = preg_match_all(
                '/<h[1-6].*id="(?<id>.*)".*<\/h[1-6]>/U',
                $htmlContent,
                $matches,
                PREG_SET_ORDER
            );

            if ($numHeadings > 0) {
                foreach ($matches as $match) {
                    $relativeUri = '#' . $match['id'];
                    $absoluteUri = $chunk->getRelativePathname() . $relativeUri;

                    $internalLinkMapper[$relativeUri] = $absoluteUri;
                }
            }
        }

        // replace the internal relative URIs for the mapped absolute URIs
        foreach ($generatedChunks as $chunk) {
            $htmlContent = file_get_contents($chunk->getPathname());

            $htmlContent = preg_replace_callback(
                '/<a href="(?<uri>#.*)"(.*)<\/a>/Us',
                function ($matches) use ($internalLinkMapper) {
                    if (isset($internalLinkMapper[$matches['uri']])) {
                        $newUri = $internalLinkMapper[$matches['uri']];
                    } else {
                        $newUri = $matches['uri'];
                    }

                    // First check if there is an existing class attribute before
                    // adding a new class attribute and breaking the XHTML syntax
                    if (preg_match('/\bclass="(.*)"/Us', $matches[1]) !== false) {
                        $matches[2] = preg_replace('/\bclass="(.*)"/Us', 'class="internal $1"', $matches[2]);
                        return sprintf('<a href="%s"%s</a>', $newUri, $matches[2]);
                    }
                    return sprintf('<a class="internal" href="%s"%s</a>', $newUri, $matches[2]);
                },
                $htmlContent
            );

            $this->filesystem->dumpFile($chunk->getPathname(), $htmlContent);
        }
    }

    private function ensureDirectoryExists(string $targetDir, string $source): void
    {
        if (file_exists($targetDir)) {
            return;
        }

        throw new RuntimeException(sprintf(
            "Books %s couldn't be copied because the given '%s' directory doesn't exist.",
            $source,
            $targetDir
        ));
    }
}
