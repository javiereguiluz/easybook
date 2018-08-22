<?php declare(strict_types=1);

namespace Easybook\Publishers;

use Easybook\Util\BetterFilesystem;
use ZendPdf\PdfDocument;

/**
 * @todo use open-sourced wkhtmlpdf
 *
 * It publishes the book as a PDF file. All the internal links are transformed
 * into clickable cross-section book links. These links even display automatically
 * the page number where they point into, so no information is lost when printing
 * the book.
 */
final class PdfPublisher extends AbstractPublisher
{
    /**
     * @var BetterFilesystem
     */
    private $betterFilesystem;

    public function __construct(BetterFilesystem $betterFilesystem)
    {
        $this->betterFilesystem = $betterFilesystem;
    }

    public function loadContents(): void
    {
        parent::loadContents();

        // if the book includes its own cover as a PDF file,
        // remove the default 'cover' element to prevent
        // publishing a book with two covers
        if ($this->getCustomCover() !== null) {
            $bookItems = [];

            // remove any element of type 'cover' from the
            // publishing items
            foreach ($this->app['publishing.items'] as $item) {
                if ($item['config']['element'] !== 'cover') {
                    $bookItems[] = $item;
                }
            }

            $this->app['publishing.items'] = $bookItems;
        }
    }

    public function assembleBook(): void
    {
        $tmpDir = $this->app['app.dir.cache'] . '/' . uniqid('easybook_pdf_');
        $this->filesystem->mkdir($tmpDir);

        // implode all the contents to create the whole book
        $htmlBookFilePath = $tmpDir . '/book.html';
        $this->renderer->renderToFile('book.twig', ['items' => $this->app['publishing.items']], $htmlBookFilePath);

        // use PrinceXML to transform the HTML book into a PDF book
        // Prepare and add stylesheets before PDF conversion
        if ($this->app->edition('include_styles')) {
            $defaultStyles = $tmpDir . '/default_styles.css';
            $this->renderer->renderToFile(
                '@theme/style.css.twig',
                ['resources_dir' => $this->app['app.dir.resources'] . '/'],
                $defaultStyles
            );

//            $prince->addStyleSheet($defaultStyles);
        }

        $customCss = $this->app->getCustomTemplate('style.css');
//        if (file_exists($customCss)) {
//            $prince->addStyleSheet($customCss);
//        }

        $pdfBookFilePath = $this->app['publishing.dir.output'] . '/book.pdf';
        // throw exception on fail
//        $prince->convert_file_to_file($htmlBookFilePath, $pdfBookFilePath);

        $this->addBookCover($pdfBookFilePath, $this->getCustomCover());
    }

    /**
     * If the book defines a custom PDF cover, this method prepends it
     * to the PDF book.
     *
     * @param string $bookFilePath  The path of the original PDF book without the cover
     * @param string $coverFilePath The path of the PDF file which will be displayed as the cover of the book
     */
    public function addBookCover(string $bookFilePath, string $coverFilePath): void
    {
        if (! empty($coverFilePath)) {
            $pdfBook = PdfDocument::load($bookFilePath);
            $pdfCover = PdfDocument::load($coverFilePath);

            $pdfCover = clone $pdfCover->pages[0];
            array_unshift($pdfBook->pages, $pdfCover);

            $pdfBook->save($bookFilePath, true);
        }
    }

    /*
     * It looks for custom book cover PDF. The search order is:
     *   1. <book>/Resources/Templates/<edition-name>/cover.pdf
     *   2. <book>/Resources/Templates/pdf/cover.pdf
     *   3. <book>/Resources/Templates/cover.pdf
     */
    public function getCustomCover(string $coverFileName = 'cover.pdf'): ?string
    {
        $paths = [
            $this->app['publishing.dir.templates'] . '/' . $this->app['publishing.edition'],
            $this->app['publishing.dir.templates'] . '/pdf',
            $this->app['publishing.dir.templates'],
        ];

        return $this->betterFilesystem->getFirstExistingFile($coverFileName, $paths);
    }

    public function getFormat(): string
    {
        return 'pdf';
    }
}
