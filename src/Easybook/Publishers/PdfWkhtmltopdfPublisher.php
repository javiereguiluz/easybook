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

use mikehaertl\wkhtmlto\Pdf;
use Symfony\Component\Yaml\Yaml;

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

    /**
     * Assemble the book using wkhtmltopdf.
     *
     * Some limitations/remarks:
     *
     * - Hyphenation is unsupported. Currently managed by an
     *   external Javascript library.
     *
     * - PDF cover is not supported. Only HTML cover is supported.
     *
     * - Facing odd/even pages are unsupported.
     *
     * - Headers / Footers are set using wkhtmltopdf arguments (very limited).
     *
     * - PDF outline cannot be filtered. It will always contain all
     *   document headings.
     *
     * - TOC filtering is (very) limited, performed via XSLT
     *   transormation.
     *
     */
    public function assembleBook()
    {
        $tmpDir = $this->app['app.dir.cache'] . '/' . uniqid('easybook_pdf_');
        $this->app['filesystem']->mkdir($tmpDir);

        /** @var Pdf $wkhtmltopdf */
        $wkhtmltopdf = $this->app['wkhtmltopdf'];

        // filter out unusupported content items and extract certain values
        $extractedValues = $this->prepareBookItems();

        // render components
        $htmlBookFilePath = $this->renderBook($tmpDir);
        $htmlCoverFilePath = $this->renderHtmlCover($tmpDir);
        $tocFilePath = $this->renderToc($tmpDir, $extractedValues['toc-title']);

        // prepare global options like paper size and margins
        $globalOptions = $this->setGlobalOptions($tmpDir);

        // add the stylesheet
        $globalOptions = array_merge($globalOptions, $this->prepareStyleSheet($tmpDir));

        // prepare page options like headers and footers
        $pageOptions = $this->prepareHeaderAndFooter($tmpDir);

        // top and bottom margins need to be tweaked to make room for header/footer
        $globalOptions['margin-top'] += $pageOptions['header-spacing'];
        $globalOptions['margin-bottom'] += $pageOptions['footer-spacing'];

        // set the options as global
        $wkhtmltopdf->setOptions($globalOptions);

        // cover page
        $wkhtmltopdf->addPage($htmlCoverFilePath);

        // TOC is always first after cover
        $tocOptions = [
            'xsl-style-sheet' => $tocFilePath,
        ];
        $wkhtmltopdf->addToc($tocOptions);

        // rest of the book
        $wkhtmltopdf->addPage($htmlBookFilePath, $pageOptions);

        // do the conversion
        $pdfBookFilePath = $this->app['publishing.dir.output'] . '/book.pdf';

        if ($wkhtmltopdf->saveAs($pdfBookFilePath) === false) {
            $this->displayPdfConversionErrors($wkhtmltopdf->getError());
        }
    }

    /**
     * Looks for the executable of the wkhtmltopdf library.
     *
     * @return string The absolute path of the executable
     *
     * @throws \RuntimeException If the wkhtmltopdf executable is not found
     */
    protected function findWkhtmltopdfPath()
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

    /**
     * Ask the user for the executable location.
     *
     * @return string
     */
    protected function askForWkhtmltopdfPath()
    {
        $this->app['console.output']->write(
            sprintf(
                " In order to generate PDF files, wkhtmltopdf library must be installed. \n\n"
                . " We couldn't find wkhtmltopdf executable in any of the following directories: \n"
                . "   -> %s \n\n"
                . " If you haven't installed it yet, you can download a fully-functional demo at: \n"
                . " %s \n\n"
                . " If you have installed in a custom directory, please type its full absolute path:\n > ",
                implode($this->app['wkhtmltopdf.default_paths'], "\n   -> "),
                'http://wkhtmltopdf.org/downloads.html'
            )
        );

        $userGivenPath = trim(fgets(STDIN));

        // output a newline for aesthetic reasons
        $this->app['console.output']->write("\n");

        return $userGivenPath;
    }

    /**
     * It displays the error messages generated by the PDF conversion
     * process in a user-friendly way.
     *
     * @param string $errorMessages The array of messages generated by wkhtmltopdf
     */
    protected function displayPdfConversionErrors($errorMessages)
    {
        $this->app['console.output']->writeln("\n Wkhtmltopdf errors and warnings");
        $this->app['console.output']->writeln(" -------------------------------\n");
        $this->app['console.output']->writeln($errorMessages);
        $this->app['console.output']->writeln("\n");
    }

    /**
     * It returns the needed configuration to set up the custom wkhtmltopdf path
     * using YAML format.
     *
     * @return string The sample YAML configuration
     */
    private function getSampleYamlConfiguration()
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

    /**
     * Set global wkhtmptopdf options.
     *
     * @param $tmpDir
     *
     * @return array options
     */
    protected function setGlobalOptions($tmpDir)
    {
        // margins and media size
        // TODO: allow other units (inches, cms)
        $marginTop = str_replace('mm', '', $this->app->edition('margin')['top']);
        $marginBottom = str_replace('mm', '', $this->app->edition('margin')['bottom']);
        $marginLeft = str_replace('mm', '', $this->app->edition('margin')['inner']);
        $marginRight = str_replace(
            'mm',
            '',
            isset($this->app->edition('margin')['outer'])
                ? $this->app->edition('margin')['outer']
                : $this->app->edition('margin')['outter']
        );

        $orientation = $this->app->edition('orientation') ?: 'portrait';

        $newOptions = [
            'page-size'     => $this->app->edition('page_size'),
            'margin-top'    => $marginTop ?: 25,
            'margin-bottom' => $marginBottom ?: 25,
            'margin-left'   => $marginLeft ?: 30,
            'margin-right'  => $marginRight ?: 20,
            'orientation'   => $orientation,
            'encoding'      => 'UTF-8',
            'print-media-type',
        ];

        // misc.
        $newOptions['outline-depth'] = $this->app->edition('toc')['deep'];

        // dump outline xml for easy outline/toc debugging
        $newOptions['dump-outline'] = $tmpDir . '/outline.xml';

        return $newOptions;
    }

    /**
     * Prepare the stylesheets to use in the book.
     *
     * @param $tmpDir
     *
     * @return array $options
     */
    protected function prepareStyleSheet($tmpDir)
    {
        $newOptions = [];

        // copy the general styles if edition wants them included
        if ($this->app->edition('include_styles')) {
            $defaultStyles = $tmpDir . '/default_styles.css';
            $this->app->render(
                '@theme/wkhtmltopdf-style.css.twig',
                array('resources_dir' => $this->app['app.dir.resources'] . '/'),
                $defaultStyles
            );

            $newOptions['user-style-sheet'] = $defaultStyles;
        }

        // get the custom templates for the book
        $customCss = $this->app->getCustomTemplate('style.css');

        // concat it to the general styles or set it as default
        if (file_exists($customCss)) {
            if (isset($newOptions['user-style-sheet'])) {
                $customCssText = file_get_contents($customCss);
                file_put_contents(
                    $newOptions['user-style-sheet'],
                    "\n/* --- custom styles --- */\n" . $customCssText,
                    FILE_APPEND
                );

            } else {
                $newOptions['user-style-sheet'] = $customCss;
            }
        }

        return $newOptions;
    }

    /**
     * Prepare book items to be rendered, filtering out unsupported types
     * and extracting certain values.
     *
     * @return array options
     */
    protected function prepareBookItems()
    {
        $extracted = [];

        $newItems = [];

        foreach ($this->app['publishing.items'] as $item) {

            // extract toc title
            if ($item['config']['element'] === 'toc') {
                $extracted['toc-title'] = $item['title'];
            }

            // exclude unsupported items
            // - toc: added by wkhtmltopdf
            // - lof: no way to render with page numbers
            // - cover: added after document generation
            if (!in_array($item['config']['element'], ['toc', 'lof', 'cover'])) {
                $newItems[] = $item;
            }
        }

        $this->app['publishing.items'] = $newItems;

        return $extracted;
    }

    /**
     * Render header and footer html files and configure options.
     *
     * @param $tmpDir
     *
     * @return array options
     */
    protected function prepareHeaderAndFooter($tmpDir)
    {
        $newOptions = [];

        $headerFooterFile = $tmpDir . '/header-footer.yml';
        $this->app->render(
            '@theme/wkhtmltopdf-header-footer.yml.twig',
            [],
            $headerFooterFile
        );

        $values = Yaml::parse(file_get_contents($headerFooterFile));

        $newOptions['header-spacing'] = $values['header']['spacing'] ?: 20;
        $newOptions['header-font-name'] = $values['header']['font-name'] ?: 'sans-serif';
        $newOptions['header-font-size'] = $values['header']['font-size'] ?: 12;
        $newOptions['header-left'] = $values['header']['left'] ?: '[doctitle]';
        $newOptions['header-center'] = $values['header']['center'] ?: '';
        $newOptions['header-right'] = $values['header']['right'] ?: '[section]';
        if ($values['header']['line'] === true) {
            $newOptions[] = 'header-line';
        } else {
            $newOptions[] = 'no-header-line';
        }

        $newOptions['footer-spacing'] = $values['footer']['spacing'] ?: 20;
        $newOptions['footer-font-name'] = $values['footer']['font-name'] ?: 'sans-serif';
        $newOptions['footer-font-size'] = $values['footer']['font-size'] ?: 12;
        $newOptions['footer-left'] = $values['footer']['left'] ?: '';
        $newOptions['footer-center'] = $values['footer']['center'] ?: '[page]';
        $newOptions['footer-right'] = $values['footer']['right'] ?: '';
        if ($values['footer']['line'] === true) {
            $newOptions[] = 'footer-line';
        } else {
            $newOptions[] = 'no-footer-line';
        }

        return $newOptions;
    }

    /**
     * Render the whole book (except excluded items) and set options.
     *
     * @param $tmpDir
     *
     * @return string
     */
    protected function renderBook($tmpDir)
    {
        $htmlBookFilePath = $tmpDir . '/book.html';
        $this->app->render(
            'book.twig',
            [
                'items'         => $this->app['publishing.items'],
                'resources_dir' => $this->app['app.dir.resources'] . '/'
            ],
            $htmlBookFilePath
        );

        return $htmlBookFilePath;
    }

    /**
     * Render the cover html file.
     *
     * @param $tmpDir
     *
     * @return string
     */
    protected function renderHtmlCover($tmpDir)
    {
        $htmlCoverFilePath = $tmpDir . '/cover.html';
        $this->app->render(
            'cover.twig',
            [],
            $htmlCoverFilePath
        );

        return $htmlCoverFilePath;
    }

    /**
     * Render the TOC XSLT file.
     *
     * @param $tmpDir
     * @param $tocTitle
     *
     * @return string
     *
     */
    protected function renderToc($tmpDir, $tocTitle)
    {
        $tocFilePath = $tmpDir . '/toc.xsl';
        $this->app->render(
            'wkhtmltopdf-toc.xsl.twig',
            [
                'toc_title' => $tocTitle,
                'toc_deep'  => $this->app->edition('toc')['deep']
            ],
            $tocFilePath
        );

        return $tocFilePath;
    }
}
