<?php declare(strict_types=1);

namespace Easybook\Tests\Publisher\PdfPublisher;

use Easybook\Publisher\PdfPublisher;
use Easybook\Tests\AbstractContainerAwareTestCase;
use Iterator;
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
        $this->pdfPublisher = $this->container->get(PdfPublisher::class);
    }

    /**
     * @dataProvider provideCoverSampleData()
     *
     * @param mixed[] $existingCoverFiles
     */
    public function testBookUsesTheRightCustomCover(array $existingCoverFiles, string $coverThatShouldBeUsed): void
    {
        $app['publishing.dir.templates'] = $app['app.dir.cache'] . '/' . uniqid('phpunit_');
        //$app['publishing.edition'] = 'print';

        $this->filesystem->mkdir([
            $app['publishing.dir.templates'],
            $app['publishing.dir.templates'] . '/print',
            $app['publishing.dir.templates'] . '/pdf',
        ]);

        foreach ($existingCoverFiles as $cover) {
            $this->filesystem->touch($app['publishing.dir.templates'] . '/' . $cover);
        }

        $selectedCoverPath = $this->pdfPublisher->getCustomCover();
        $selectedCover = str_replace($app['publishing.dir.templates'] . '/', '', $selectedCoverPath);

        $this->assertSame($coverThatShouldBeUsed, $selectedCover);

        $this->filesystem->remove($app['publishing.dir.templates']);
    }

    public function provideCoverSampleData(): Iterator
    {
        yield [['print/cover.pdf', 'pdf/cover.pdf', 'cover.pdf'], 'print/cover.pdf'];
        yield [['print/cover.jpg', 'pdf/cover.pdf', 'cover.pdf'], 'pdf/cover.pdf'];
        yield [['print/cover.jpg', 'pdf/cover.png', 'cover.pdf'], 'cover.pdf'];
        yield [['pdf/cover.pdf', 'cover.pdf'], 'pdf/cover.pdf'];
        yield [['print/cover.pdf'], 'print/cover.pdf'];
        yield [['pdf/cover.pdf'], 'pdf/cover.pdf'];
        yield [['cover.pdf'], 'cover.pdf'];
        yield [['print/cover.png'], ''];
    }

    public function testAddBookCover(): void
    {
        $app = new Application();

        $tmpDir = $app['app.dir.cache'] . '/' . uniqid('phpunit_');
        $this->filesystem->mkdir($tmpDir);

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

        $this->filesystem->remove($tmpDir);
    }

    private function createPdfFile(string $filePath, string $contents): void
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
