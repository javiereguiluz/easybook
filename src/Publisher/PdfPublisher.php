<?php declare(strict_types=1);

namespace Easybook\Publisher;

use Easybook\Exception\Publisher\RequiredBinFileNotFoundException;

/**
 * @todo
 */
final class PdfPublisher extends AbstractPublisher
{
    /**
     * @var string
     */
    public const NAME = 'pdf';

    /**
     * @var string|null
     */
    private $wkhtmltopdfPath;

    /**
     * @var string[]
     */
    private $possibleWkhtmlpdfPaths = [
        # Mac OS X
        '/usr/local/bin/wkhtmltopdf',
        # Linux
        '/usr/bin/wkhtmltopdf',
        # Windows TODO: write the actual path
        'C:\Program Files\wkhtmltopdf.exe',
    ];

    public function __construct(?string $wkhtmltopdfPath)
    {
        $this->wkhtmltopdfPath = $wkhtmltopdfPath;
    }

    public function getFormat(): string
    {
        return self::NAME;
    }

    public function loadContents(): void
    {
        parent::loadContents();

        // if the book includes its own cover as a PDF file,
        // remove the default 'cover' element to prevent
        // publishing a book with two covers
        if (null !== $this->getCustomCover()) {
            $bookItems = array();

            // remove any element of type 'cover' from the
            // publishing items
            foreach ($this->app['publishing.items'] as $item) {
                if ('cover' != $item['config']['element']) {
                    $bookItems[] = $item;
                }
            }

            $this->app['publishing.items'] = $bookItems;
        }
    }

    /*
 /**
  * It looks for custom book cover PDF. The search order is:
  *   1. <book>/Resources/Templates/<edition-name>/cover.pdf
  *   2. <book>/Resources/Templates/pdf/cover.pdf
@@ -201,35 +75,18 @@ public function addBookCover($bookFilePath, $coverFilePath)
  * @param string $coverFileName The name of the PDF file that defines the book cover
  *
  * @return null|string The filePath of the PDF cover or null if none exists
  *
  * @protected (set to public to be able to unit test it)
  */
    public function getCustomCover($coverFileName = 'cover.pdf')
    {
        $paths = array(
            $this->app['publishing.dir.templates'].'/'.$this->app['publishing.edition'],
            $this->app['publishing.dir.templates'].'/pdf',
            $this->app['publishing.dir.templates'] . '/' . $this->app['publishing.edition'],
            $this->app['publishing.dir.templates'] . '/pdf',
            $this->app['publishing.dir.templates'],
        );
        return $this->app->getFirstExistingFile($coverFileName, $paths);
    }

    /**
     * If the book defines a custom PDF cover, this method prepends it
     * to the PDF book.
     *
     * @param string $bookFilePath  The path of the original PDF book without the cover
     * @param string $coverFilePath The path of the PDF file which will be displayed as the cover of the book
     *
     * @protected (set to public to be able to unit test it)
     */
    public function addBookCover(string $bookFilePath, string $coverFilePath): void
    {
        if (!empty($coverFilePath)) {
            $pdfBook = PdfDocument::load($bookFilePath);
            $pdfCover = PdfDocument::load($coverFilePath);

            $pdfCover = clone $pdfCover->pages[0];
            array_unshift($pdfBook->pages, $pdfCover);

            $pdfBook->save($bookFilePath, true);
        }
    }

    /**
     * @todo Make part of internal API
     *
     * Decorates each book item with the appropriate Twig template.
     */
    public function decorateContents(): void
    {
//        foreach ($this->publishingItems as $item) {
//            $item->changeContent($this->renderer->render($item->getConfigElement() . '.twig', [
//                'item' => $item,
//            ]));
//        }
    }

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
    /**
     * It publishes the book as a PDF file. All the internal links are transformed
     * into clickable cross-section book links. These links even display automatically
     * the page number where they point into, so no information is lost when printing
     * the book.
     */
class PdfWkhtmltopdfPublisher extends PdfPublisher
{
    public function checkIfThisPublisherIsSupported()
    {
        if (null !== $this->app['wkhtmltopdf.path'] && file_exists($this->app['wkhtmltopdf.path'])) {
            $wkhtmltopdfPath = $this->app['wkhtmltopdf.path'];
        } else {
            $wkhtmltopdfPath = $this->findWkhtmltopdfPath();
        }
        $this->app['wkhtmltopdf.path'] = $wkhtmltopdfPath;
        return null !== $wkhtmltopdfPath && file_exists($wkhtmltopdfPath);
    }
    public function assembleBook(): void
    {
        $tmpDir = $this->app['app.dir.cache'] . '/' . uniqid('easybook_pdf_');
        $this->app['filesystem']->mkdir($tmpDir);
        // implode all the contents to create the whole book
        $htmlBookFilePath = $tmpDir . '/book.html';
        $this->app->render(
            'book.twig',
            array('items' => $this->app['publishing.items']),
            $htmlBookFilePath
        );
        //        // use khtmltopdf to transform the HTML book into a PDF book
//        $prince = $this->app['prince'];
//        $prince->setBaseURL($this->app['publishing.dir.contents'] . '/images');
//
//        // Prepare and add stylesheets before PDF conversion
//        if ($this->app->edition('include_styles')) {
//            $defaultStyles = $tmpDir . '/default_styles.css';
//            $this->app->render(
//                '@theme/style.css.twig',
//                array('resources_dir' => $this->app['app.dir.resources'] . '/'),
//                $defaultStyles
//            );
//
//            $prince->addStyleSheet($defaultStyles);
//        }
//
//        $customCss = $this->app->getCustomTemplate('style.css');
//        if (file_exists($customCss)) {
//            $prince->addStyleSheet($customCss);
//        }
//
//        $errorMessages = array();
//        $pdfBookFilePath = $this->app['publishing.dir.output'] . '/book.pdf';
//        $prince->convert_file_to_file($htmlBookFilePath, $pdfBookFilePath, $errorMessages);
//        $this->displayPdfConversionErrors($errorMessages);
//
//        $this->addBookCover($pdfBookFilePath, $this->getCustomCover());
    }
    /**
     * Looks for the executable of the wkhtmltopdf library.
     *
     * @return string The absolute path of the executable
     *
     * @throws \RuntimeException If the wkhtmltopdf executable is not found
     */
    protected function findWkhtmltopdfPath(): string
    {
        foreach ($this->app['wkhtmltopdf.default_paths'] as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        // the executable couldn't be found in the common
        // installation directories. Ask the user for the path
        $isInteractive = null !== $this->app['console.input'] && $this->app['console.input']->isInteractive();
        if ($isInteractive) {
            return $this->askForWkhtmltopdfPath();
        }
        throw new \RuntimeException(
            sprintf(
                "ERROR: The wkhtmltopdf library needed to generate PDF books cannot be found.\n"
                . " Check that you have installed wkhtmltopdf in a common directory \n"
                . " or set your custom wkhtmltopdf path in the book's config.yml file:\n\n"
                . '%s',
                $this->getSampleYamlConfiguration()
            )
        );
    }
    protected function askForWkhtmltopdfPath()
    {
        $this->app['console.output']->write(
            sprintf(
                " In order to generate PDF files, PrinceXML library must be installed. \n\n"
                . " We couldn't find PrinceXML executable in any of the following directories: \n"
                . "   -> %s \n\n"
                . " If you haven't installed it yet, you can download a fully-functional demo at: \n"
                . " %s \n\n"
                . " If you have installed in a custom directory, please type its full absolute path:\n > ",
                implode($this->app['prince.default_paths'], "\n   -> "),
                'http://wkhtmltopdf.org/downloads.html'
            )
        );
        $userGivenPath = trim(fgets(STDIN));
        // output a newline for aesthetic reasons
        $this->app['console.output']->write("\n");
        return $userGivenPath;
    }
    //    /**
//     * It displays the error messages generated by the PDF conversion
//     * process in a user-friendly way.
//     *
//     * @param array $errorMessages The array of messages generated by PrinceXML
//     */
//    protected function displayPdfConversionErrors($errorMessages)
//    {
//        if (count($errorMessages) > 0) {
//            $this->app['console.output']->writeln("\n PrinceXML errors and warnings");
//            $this->app['console.output']->writeln(" -----------------------------\n");
//
//            foreach ($errorMessages as $message) {
//                $this->app['console.output']->writeln(
//                    '   [' . strtoupper($message[0]) . '] ' . ucfirst($message[2]) . ' (' . $message[1] . ')'
//                );
//            }
//
//            $this->app['console.output']->writeln("\n");
//        }
//    }
    /**
     * It returns the needed configuration to set up the custom wkhtmltopdf path
     * using YAML format.
     *
     * @return string The sample YAML configuration
     */
    private function getSampleYamlConfiguration(): string
    {
        return <<<YAML
  easybook:
      parameters:
          wkhtmltopdf.path: '/path/to/utils/wkhtmltopdf'
   book:
      title:  ...
      author: ...
      # ...
YAML;
    }
}

    protected function assembleBook(string $outputDirectory): string
    {
        $this->ensureExistingWkhtmlpdfPathIsSet();

        return $outputDirectory . '/book.pdf';
    }

    /**
     * @todo decouple for this and mobi
     */
    private function ensureExistingWkhtmlpdfPathIsSet(): void
    {
        if ($this->wkhtmltopdfPath === null) {
            foreach ($this->possibleWkhtmlpdfPaths as $possibleWkhtmlpdfPath) {
                if (file_exists($possibleWkhtmlpdfPath)) {
                    $this->wkhtmltopdfPath = $possibleWkhtmlpdfPath;
                    return;
                }
            }

            throw new RequiredBinFileNotFoundException(sprintf(
                'Wkthmlpdf bin is required to create pdf. The path to is empty though. We also looked into "%s" but did not find it. Set it in "parameters > kindlegen_path".',
                implode('", "', $this->possibleWkhtmlpdfPaths)
            ));
        }

        if (file_exists($this->wkhtmltopdfPath)) {
            return;
        }

        throw new RequiredBinFileNotFoundException(sprintf(
            'Wkhtmlpdf bin was not found in "%s" path provided in "parameters > wkhtmlpdf_path".',
            $this->wkhtmltopdfPath
        ));
    }
}
