<?php declare(strict_types=1);

namespace Easybook\Tests\Publishers;

use Easybook\Publishers\PdfPublisher;
use Easybook\Tests\AbstractContainerAwareTestCase;
use ZendPdf\Font;
use ZendPdf\Page;
use ZendPdf\PdfDocument;

final class PdfPublisherTest extends AbstractContainerAwareTestCase
{
    /**
     * @var PdfPublisher
     */
    private $pdfPublisher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdfPublisher = $this->container->get(PdfPublisher::class);
    }

    /**
     * @dataProvider provideCoverSampleData
     */
    public function testBookUsesTheRightCustomCover($existingCoverFiles, $coverThatShouldBeUsed): void
    {
        $app['publishing.dir.templates'] = $app['app.dir.cache'] . '/' . uniqid('phpunit_');
        $app['publishing.edition'] = 'print';

        $app['filesystem']->mkdir([
            $app['publishing.dir.templates'],
            $app['publishing.dir.templates'] . '/print',
            $app['publishing.dir.templates'] . '/pdf',
        ]);

        foreach ($existingCoverFiles as $cover) {
            $app['filesystem']->touch($app['publishing.dir.templates'] . '/' . $cover);
        }

        $selectedCoverPath = $this->pdfPublisher->getCustomCover();
        $selectedCover = str_replace($app['publishing.dir.templates'] . '/', '', $selectedCoverPath);

        $this->assertSame($coverThatShouldBeUsed, $selectedCover);

        $app['filesystem']->remove($app['publishing.dir.templates']);
    }

    public function provideCoverSampleData()
    {
        return [
            [
                ['print/cover.pdf', 'pdf/cover.pdf', 'cover.pdf'],
                'print/cover.pdf',
            ],
            [
                ['print/cover.jpg', 'pdf/cover.pdf', 'cover.pdf'],
                'pdf/cover.pdf',
            ],
            [
                ['print/cover.jpg', 'pdf/cover.png', 'cover.pdf'],
                'cover.pdf',
            ],
            [
                ['pdf/cover.pdf', 'cover.pdf'],
                'pdf/cover.pdf',
            ],
            [
                ['print/cover.pdf'],
                'print/cover.pdf',
            ],
            [
                ['pdf/cover.pdf'],
                'pdf/cover.pdf',
            ],
            [
                ['cover.pdf'],
                'cover.pdf',
            ],
            [
                ['print/cover.png'],
                '',
            ],
        ];
    }

    public function testOneSidedPrintedBookDontIncludeBlankPages(): void
    {
        $app['publishing.book.config'] = $this->getBookConfig(false);
        $app['publishing.edition'] = 'print';

        $bookCss = $app->render('@theme/style.css.twig');

        $this->assertNotContains(
            ".item {\n    page-break-before: right;",
            $bookCss,
            "One-sided books don't include blank pages."
        );
    }

    public function testTwoSidedPrintedBookIncludeBlankPages(): void
    {
        $app['publishing.book.config'] = $this->getBookConfig(true);
        $app['publishing.edition'] = 'print';

        $bookCss = $app->render('@theme/style.css.twig');

        $this->assertContains(
            ".item {\n    page-break-before: right;",
            $bookCss,
            'Two-sided books include blank pages when needed.'
        );
    }

    public function testAddBookCover(): void
    {
        $tmpDir = $app['app.dir.cache'] . '/' . uniqid('phpunit_');
        $app['filesystem']->mkdir($tmpDir);

        $coverFilePath = $tmpDir . '/cover.pdf';
        $bookFilePath = $tmpDir . '/book.pdf';

        $this->createPdfFile($coverFilePath, 'EASYBOOK COVER');
        $this->createPdfFile($bookFilePath, 'easybook contents');

        $this->pdfPublisher->addBookCover($bookFilePath, $coverFilePath);

        $resultingPdfBook = PdfDocument::load($bookFilePath);

        $this->assertCount(2, $resultingPdfBook->pages, 'The cover page has been added to the book.');

        $this->assertFileExists($coverFilePath, 'The cover PDF file is NOT deleted after adding it to the book.');

        $this->assertContains(
            'EASYBOOK COVER',
            $resultingPdfBook->render(),
            'The resulting book contains the cover text.'
        );
        $this->assertContains(
            'easybook contents',
            $resultingPdfBook->render(),
            'The resulting book contains the original book contents.'
        );

        $app['filesystem']->remove($tmpDir);
    }

    private function getBookConfig($twoSided)
    {
        return [
            'book' => [
                'language' => 'en',
                'editions' => [
                    'print' => [
                        'format' => 'pdf',
                        'theme' => 'clean',
                        'two_sided' => $twoSided,
                    ],
                ],
            ],
        ];
    }

    private function createPdfFile($filePath, $contents): void
    {
        $pdf = new PdfDocument();

        $page = new Page(Page::SIZE_A4);

        $font = Font::fontWithName(Font::FONT_HELVETICA);
        $page->setFont($font, 18);
        $page->drawText($contents, 50, 780);

        $pdf->pages[] = $page;

        $pdf->save($filePath);
    }
}
