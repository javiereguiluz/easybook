<?php declare(strict_types=1);

namespace Easybook\Tests\Publisher;

use Easybook\DependencyInjection\Application;
use Easybook\Publisher\PdfWkhtmltopdfPublisher;
use Easybook\Tests\AbstractContainerAwareTestCase;
use RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Throwable;
use ZendPdf\Font;
use ZendPdf\Page;
use ZendPdf\PdfDocument;

final class PdfPublisherTest extends AbstractContainerAwareTestCase
{
    public function testHelpIsDisplayedWhenThisPublisherIsNotSupported(): void
    {
        $app = new Application();

        $app['wkhtmltopdf.path'] = null;
        $app['wkhtmltopdf.default_paths'] = [];
        $app['console.input'] = null;

        $helpMessage = <<<HELP
  easybook:
      parameters:
          wkhtmltopdf.path: '/path/to/utils/wkhtmltopdf'
HELP;

        $publisher = new PdfWkhtmltopdfPublisher($app);

        try {
            $publisher->checkIfThisPublisherIsSupported();
        } catch (Throwable $e) {
            $this->assertInstanceOf(RuntimeException::class, $e);
            $this->assertContains($helpMessage, $e->getMessage());
        }
    }

    public function testUserGivesWrongWkhtmltopdfPath(): void
    {
        $app = new Application();

        $app['wkhtmltopdf.path'] = null;
        $app['wkhtmltopdf.default_paths'] = [];
        $app['console.input'] = new ArrayInput([]);

        $publisher = $this->getMock(
            'Easybook\Publishers\PdfWkhtmltopdfPublisher',
            ['askForwkhtmltopdfPath'],
            [$app]
        );

        $publisher->expects($this->once())
            ->method('askForwkhtmltopdfPath')
            ->will($this->returnValue(uniqid('this_path_does_not_exist_')));

        $this->assertFalse(
            $publisher->checkIfThisPublisherIsSupported(),
            'The given wkhtmltopdf path is wrong, so this publisher is not supported'
        );
    }

    public function testUserGivesCorrectwkhtmltopdfPath(): void
    {
        $app = new Application();

        $app['wkhtmltopdf.path'] = null;
        $app['wkhtmltopdf.default_paths'] = [];
        $app['console.input'] = new ArrayInput([]);

        $publisher = $this->getMock(
            'Easybook\Publishers\PdfWkhtmltopdfPublisher',
            ['askForwkhtmltopdfPath'],
            [$app]
        );

        $publisher->expects($this->once())
            ->method('askForwkhtmltopdfPath')
            ->will($this->returnValue(__FILE__));

        $this->assertTrue(
            $publisher->checkIfThisPublisherIsSupported(),
            'The given wkhtmltopdf path is correct, so this publisher is supported'
        );
    }

    public function testThisPublisherIsSupported(): void
    {
        $app = new Application();

        // for this test, any valid path is enough to simulate wkhtmltopdf
        $wkhtmltopdfPath = __FILE__;

        $app['wkhtmltopdf.path'] = $wkhtmltopdfPath;
        $publisher = new PdfWkhtmltopdfPublisher($app);
        $this->assertTrue(
            $publisher->checkIfThisPublisherIsSupported(),
            'If the wkhtmltopdf path exists, this publisher is supported'
        );

        $app['wkhtmltopdf.path'] = null;
        $app['wkhtmltopdf.default_paths'] = [$wkhtmltopdfPath];
        $publisher = new PdfWkhtmltopdfPublisher($app);
        $this->assertTrue(
            $publisher->checkIfThisPublisherIsSupported(),
            'If the wkhtmltopdf default path exists, this publisher is supported'
        );

        $app['wkhtmltopdf.path'] = null;
        $app['wkhtmltopdf.default_paths'] = [null, null, $wkhtmltopdfPath];
        $publisher = new PdfWkhtmltopdfPublisher($app);
        $this->assertTrue(
            $publisher->checkIfThisPublisherIsSupported(),
            'If any of the wkhtmltopdf default paths exists, this publisher is supported'
        );
    }

    /**
     * @dataProvider provideCoverSampleData
     */
    public function testBookUsesTheRightCustomCover($existingCoverFiles, $coverThatShouldBeUsed): void
    {
        $app = new Application();
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

        $publisher = new PdfWkhtmltopdfPublisher($app);

        $selectedCoverPath = $publisher->getCustomCover();
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
        $app = new Application();
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
        $app = new Application();
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
        $app = new Application();

        $tmpDir = $app['app.dir.cache'] . '/' . uniqid('phpunit_');
        $app['filesystem']->mkdir($tmpDir);

        $coverFilePath = $tmpDir . '/cover.pdf';
        $bookFilePath = $tmpDir . '/book.pdf';

        $this->createPdfFile($coverFilePath, 'EASYBOOK COVER');
        $this->createPdfFile($bookFilePath, 'easybook contents');

        $publisher = new PdfWkhtmltopdfPublisher($app);
        $publisher->addBookCover($bookFilePath, $coverFilePath);

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
