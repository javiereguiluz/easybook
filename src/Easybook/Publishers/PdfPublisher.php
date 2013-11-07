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

use ZendPdf\PdfDocument;

/**
 * It publishes the book as a PDF file. All the internal links are transformed
 * into clickable cross-section book links. These links even display automatically
 * the page number where they point into, so no information is lost when printing
 * the book.
 */
class PdfPublisher extends BasePublisher
{
    public function checkIfThisPublisherIsSupported()
    {
        if (null != $this->app['prince.path'] && file_exists($this->app['prince.path'])) {
            $princeXMLPath = $this->app['prince.path'];
        } else {
            $princeXMLPath = $this->findPrinceXMLPath();
        }

        $this->app['prince.path'] = $princeXMLPath;

        return null != $princeXMLPath && file_exists($princeXMLPath);
    }

    public function loadContents()
    {
        parent::loadContents();

        // if the book includes its own cover as a PDF file,
        // remove the default 'cover' element to prevent
        // publishing a book with two covers
        if (null != $this->getCustomCover()) {
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

    public function assembleBook()
    {
        $tmpDir = $this->app['app.dir.cache'].'/'.uniqid('easybook_pdf_');
        $this->app['filesystem']->mkdir($tmpDir);

        // implode all the contents to create the whole book
        $htmlBookFilePath = $tmpDir.'/book.html';
        $this->app->render(
            'book.twig',
            array('items' => $this->app['publishing.items']),
            $htmlBookFilePath
        );

        // use PrinceXML to transform the HTML book into a PDF book
        $prince = $this->app['prince'];
        $prince->setBaseURL($this->app['publishing.dir.contents'].'/images');

        // Prepare and add stylesheets before PDF conversion
        if ($this->app->edition('include_styles')) {
            $defaultStyles = $tmpDir.'/default_styles.css';
            $this->app->render(
                '@theme/style.css.twig',
                array('resources_dir' => $this->app['app.dir.resources'].'/'),
                $defaultStyles
            );

            $prince->addStyleSheet($defaultStyles);
        }

        $customCss = $this->app->getCustomTemplate('style.css');
        if (file_exists($customCss)) {
            $prince->addStyleSheet($customCss);
        }

        $errorMessages = array();
        $pdfBookFilePath = $this->app['publishing.dir.output'].'/book.pdf';
        $prince->convert_file_to_file($htmlBookFilePath, $pdfBookFilePath, $errorMessages);

        // display PDF conversion errors
        if (count($errorMessages) > 0) {
            $this->app['console.output']->writeln("\n PrinceXML errors and warnings");
            $this->app['console.output']->writeln(" -----------------------------\n");
            foreach ($errorMessages as $message) {
                $this->app['console.output']->writeln(
                    '   ['.strtoupper($message[0]).'] '.ucfirst($message[2]).' ('.$message[1].')'
                );
            }
            $this->app['console.output']->writeln("\n");
        }

        // add the PDF cover if the book includes it
        if (null != $coverFilePath = $this->getCustomCover()) {
            $pdfBook  = PdfDocument::load($pdfBookFilePath);
            $pdfCover = PdfDocument::load($coverFilePath);

            $pdfCover = clone $pdfCover->pages[0];
            array_unshift($pdfBook->pages, $pdfCover);

            $pdfBook->save($pdfBookFilePath, true);
        }
    }

    /**
     * Looks for the executable of the PrinceXML library.
     *
     * @return string The absolute path of the executable
     * @throws \RuntimeException If the PrinceXML executable is not found
     */
    public function findPrinceXMLPath()
    {
        foreach ($this->app['prince.default_paths'] as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // the executable couldn't be found in the common
        // installation directories. Ask the user for the path
        $isInteractive = null != $this->app['console.input'] && $this->app['console.input']->isInteractive();
        if ($isInteractive) {
            return $this->askForPrinceXMLPath();
        }

        throw new \RuntimeException(sprintf(
            "ERROR: The PrinceXML library needed to generate PDF books cannot be found.\n"
                ." Check that you have installed PrinceXML in a common directory \n"
                ." or set your custom PrinceXML path in the book's config.yml file:\n\n"
                ."%s",
            $this->getSampleYamlConfiguration()
        ));
    }

    public function askForPrinceXMLPath()
    {
        $this->app['console.output']->write(sprintf(
            " In order to generate PDF files, PrinceXML library must be installed. \n\n"
                ." We couldn't find PrinceXML executable in any of the following directories: \n"
                ."   -> %s \n\n"
                ." If you haven't installed it yet, you can download a fully-functional demo at: \n"
                ." %s \n\n"
                ." If you have installed in a custom directory, please type its full absolute path:\n > ",
            implode($this->app['prince.default_paths'], "\n   -> "),
            'http://www.princexml.com/download'
        ));

        $userGivenPath = trim(fgets(STDIN));

        // output a newline for aesthetic reasons
        $this->app['console.output']->write("\n");

        return $userGivenPath;
    }

    /*
     * It looks for custom book cover PDF. The search order is:
     *   1. <book>/Resources/Templates/<edition-name>/cover.pdf
     *   2. <book>/Resources/Templates/pdf/cover.pdf
     *   3. <book>/Resources/Templates/cover.pdf
     *
     * @return null|string The filePath of the PDF cover or null if none exists
     */
    public function getCustomCover()
    {
        $coverFileName = 'cover.pdf';
        $paths = array(
            $this->app['publishing.dir.templates'].'/'.$this->app['publishing.edition'],
            $this->app['publishing.dir.templates'].'/pdf',
            $this->app['publishing.dir.templates']
        );

        return $this->app->getFirstExistingFile($coverFileName, $paths);
    }

    /**
     * It returns the needed configuration to set up the custom PrinceXML path
     * using YAML format.
     *
     * @return string The sample YAML configuration
     */
    private function getSampleYamlConfiguration()
    {
        return <<<YAML
  easybook:
      parameters:
          prince.path: '/path/to/utils/PrinceXML/prince'

  book:
      title:  ...
      author: ...
      # ...
YAML;
    }
}
