<?php declare(strict_types=1);

namespace Easybook\Publisher;

use Easybook\Book\Edition;
use Easybook\Exception\Publisher\PublisherException;
use Easybook\Exception\Publisher\RequiredBinFileNotFoundException;
use mikehaertl\wkhtmlto\Pdf;

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
        'C:\Program Files\wkhtmltopdf\wkhtmltopdf.exe',
    ];

    /**
     * @var Pdf
     */
    private $pdf;

    /**
     * @var string
     */
    private $kernelCacheDir;

    public function __construct(?string $wkhtmltopdfPath, Pdf $pdf, string $kernelCacheDir)
    {
        $this->wkhtmltopdfPath = $wkhtmltopdfPath;
        $this->pdf = $pdf;
        $this->kernelCacheDir = $kernelCacheDir;
    }

    public function getFormat(): string
    {
        return self::NAME;
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
     * - Two-sided pages are unsupported.
     *
     * - Headers / Footers are set using wkhtmltopdf arguments (very limited).
     *
     * - PDF outline cannot be filtered. It will always contain all
     *   document headings.
     *
     * - TOC filtering is (very) limited, performed via XSLT
     *   tranfsormation.
     */
    public function assembleBook(string $outputDirectory, Edition $edition): string
    {
        $this->ensureExistingWkhtmlpdfPathIsSet();

        $tmpDir = $this->kernelCacheDir . '/' . uniqid('easybook_pdf_');
        $this->filesystem->mkdir($tmpDir);

        // consolidate book images to temp dir
        $imagesDir = $tmpDir . '/images';
        $this->filesystem->mkdir($imagesDir);
        $this->prepareBookImages($imagesDir);

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
        $pageOptions = $this->prepareHeaderAndFooter($tmpDir, $edition);

        // top and bottom margins need to be tweaked to make room for header/footer
        $globalOptions['margin-top'] += $pageOptions['header-spacing'];
        $globalOptions['margin-bottom'] += $pageOptions['footer-spacing'];

        // set the options as global
        $this->pdf->setOptions($globalOptions);

        // cover page
        $this->pdf->addPage($htmlCoverFilePath);

        // TOC is always first after cover
        $tocOptions = [
            'xsl-style-sheet' => $tocFilePath,
        ];
        $this->pdf->addToc($tocOptions);

        // rest of the book
        $this->pdf->addPage($htmlBookFilePath, $pageOptions);

        // do the conversion
        $pdfBookFilePath = $outputDirectory . '/book.pdf';

        if ($this->pdf->saveAs($pdfBookFilePath) === false) {
            throw new PublisherException($this->pdf->getError());
        }

        return $outputDirectory . '/book.pdf';
    }

    /**
     * Prepare book items to be rendered, filtering out unsupported types
     * and extracting certain values.
     *
     * @return mixed[] options
     */
    protected function prepareBookItems(): array
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
            // - lof and lot: no way to render with page numbers
            // - cover: added after document generation
            if (! in_array($item['config']['element'], ['toc', 'lof', 'lot', 'cover'], true)) {
                $newItems[] = $item;
            }
        }

        $this->app['publishing.items'] = $newItems;

        return $extracted;
    }

    /**
     * Render header and footer html files and configure options.
     *
     * @return mixed[] options
     */
    protected function prepareHeaderAndFooter(string $tmpDir, Edition $edition): array
    {
        $newOptions = [];

        $headerFooterFile = $tmpDir . '/header-footer.yml';
        $this->renderer->renderToFile('@theme/wkhtmltopdf-header-footer.yml.twig', [], $headerFooterFile);

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
     */
    protected function renderBook(string $tmpDir): string
    {
        $htmlBookFilePath = $tmpDir . '/book.html';
        $this->renderer->renderToFile(
            'book.twig',
            [
                'items' => $this->app['publishing.items'],
                'resources_dir' => $this->app['app.dir.resources'] . '/',
            ],
            $htmlBookFilePath
        );

        return $htmlBookFilePath;
    }

    /**
     * Render the TOC XSLT file.
     */
    protected function renderToc(string $tmpDir, string $tocTitle, Edition $edition): string
    {
        $tocFilePath = $tmpDir . '/toc.xsl';
        $toc = $edition->getTableOfContents();

        $this->renderer->render(
            'wkhtmltopdf-toc.xsl.twig',
            [
                'toc_title' => $tocTitle,
                'toc_deep' => $toc['deep'],
            ],
            $tocFilePath
        );

        return $tocFilePath;
    }

    /**
     * Set global wkhtmptopdf options.
     *
     * @return mixed[] options
     */
    private function setGlobalOptions(string $tmpDir): array
    {
        // margins and media size
        // TODO: allow other units (inches, cms)
        $margin = $this->app->edition('margin');
        $marginTop = str_replace('mm', '', $margin['top']);
        $marginBottom = str_replace('mm', '', $margin['bottom']);
        $marginLeft = str_replace('mm', '', $margin['inner']);
        $marginRight = str_replace('mm', '', $margin['outer'] ?? $margin['outter']);

        $orientation = $this->app->edition('orientation') ?: 'portrait';

        $newOptions = [
            'page-size' => $this->app->edition('page_size'),
            'margin-top' => $marginTop ?: 25,
            'margin-bottom' => $marginBottom ?: 25,
            'margin-left' => $marginLeft ?: 30,
            'margin-right' => $marginRight ?: 20,
            'orientation' => $orientation,
            'encoding' => 'UTF-8',
            'print-media-type',
        ];

        // misc.
        $edition = $this->app->edition('toc');
        $newOptions['outline-depth'] = $edition['deep'];

        // dump outline xml for easy outline/toc debugging
        $newOptions['dump-outline'] = $tmpDir . '/outline.xml';

        return $newOptions;
    }

    /**
     * Prepare the stylesheets to use in the book.
     *
     * @return mixed[] $options
     */
    private function prepareStyleSheet(string $tmpDir): array
    {
        $newOptions = [];

        // copy the general styles if edition wants them included
        $defaultStyles = $tmpDir . '/default_styles.css';
        $this->renderer->render(
            '@theme/wkhtmltopdf-style.css.twig',
            ['resources_dir' => $this->app['app.dir.resources'] . '/'],
            $defaultStyles
        );

        $newOptions['user-style-sheet'] = $defaultStyles;

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

    private function renderHtmlCover(string $tmpDir): string
    {
        $htmlCoverFilePath = $tmpDir . '/cover.html';
        $this->renderer->renderToFile('cover.twig', [], $htmlCoverFilePath);

        return $htmlCoverFilePath;
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
