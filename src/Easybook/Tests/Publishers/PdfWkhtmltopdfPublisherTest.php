<?php

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Easybook\Tests\Publishers;

use Easybook\Publishers\PdfWkhtmltopdfPublisher;
use Symfony\Component\Console\Input\ArrayInput;
use Easybook\DependencyInjection\Application;
use ZendPdf\PdfDocument;
use ZendPdf\Font;
use ZendPdf\Page;

class PdfWkhtmltopdfPublisherTest extends \PHPUnit_Framework_TestCase
{
    public function testHelpIsDisplayedWhenThisPublisherIsNotSupported()
    {
        $app = new Application();

        $app['wkhtmltopdf.path'] = null;
        $app['wkhtmltopdf.default_paths'] = array();
        $app['console.input'] = null;

        $helpMessage = <<<HELP
  easybook:
      parameters:
          wkhtmltopdf.path: '/path/to/utils/wkhtmltopdf'
HELP;

        $publisher = new PdfWkhtmltopdfPublisher($app);

        try {
            $publisher->checkIfThisPublisherIsSupported();
        } catch (\Exception $e) {
            $this->assertInstanceOf('RuntimeException', $e);
            $this->assertContains($helpMessage, $e->getMessage());
        }
    }

    public function testUserGivesWrongWkhtmltopdfPath()
    {
        $app = new Application();

        $app['wkhtmltopdf.path'] = null;
        $app['wkhtmltopdf.default_paths'] = array();
        $app['console.input'] = new ArrayInput(array());

        $publisher = $this->getMock('Easybook\Publishers\PdfWkhtmltopdfPublisher',
            array('askForwkhtmltopdfPath'),
            array($app)
        );

        $publisher->expects($this->once())
            ->method('askForwkhtmltopdfPath')
            ->will($this->returnValue(uniqid('this_path_does_not_exist_'))
        );

        $this->assertFalse(
            $publisher->checkIfThisPublisherIsSupported(),
            'The given wkhtmltopdf path is wrong, so this publisher is not supported'
        );
    }

    public function testUserGivesCorrectwkhtmltopdfPath()
    {
        $app = new Application();

        $app['wkhtmltopdf.path'] = null;
        $app['wkhtmltopdf.default_paths'] = array();
        $app['console.input'] = new ArrayInput(array());

        $publisher = $this->getMock('Easybook\Publishers\PdfWkhtmltopdfPublisher',
            array('askForwkhtmltopdfPath'),
            array($app)
        );

        $publisher->expects($this->once())
            ->method('askForwkhtmltopdfPath')
            ->will($this->returnValue(__FILE__)
        );

        $this->assertTrue(
            $publisher->checkIfThisPublisherIsSupported(),
            'The given wkhtmltopdf path is correct, so this publisher is supported'
        );
    }

    public function testThisPublisherIsSupported()
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
        $app['wkhtmltopdf.default_paths'] = array($wkhtmltopdfPath);
        $publisher = new PdfWkhtmltopdfPublisher($app);
        $this->assertTrue(
            $publisher->checkIfThisPublisherIsSupported(),
            'If the wkhtmltopdf default path exists, this publisher is supported'
        );

        $app['wkhtmltopdf.path'] = null;
        $app['wkhtmltopdf.default_paths'] = array(null, null, $wkhtmltopdfPath);
        $publisher = new PdfWkhtmltopdfPublisher($app);
        $this->assertTrue(
            $publisher->checkIfThisPublisherIsSupported(),
            'If any of the wkhtmltopdf default paths exists, this publisher is supported'
        );
    }

    /**
     * @dataProvider provideCoverSampleData
     */
    public function testBookUsesTheRightCustomCover($existingCoverFiles, $coverThatShouldBeUsed)
    {
        $app = new Application();
        $app['publishing.dir.templates'] = $app['app.dir.cache'].'/'.uniqid('phpunit_');
        $app['publishing.edition'] = 'print';

        $app['filesystem']->mkdir(array(
            $app['publishing.dir.templates'],
            $app['publishing.dir.templates'].'/print',
            $app['publishing.dir.templates'].'/pdf',
        ));

        foreach ($existingCoverFiles as $cover) {
            $app['filesystem']->touch($app['publishing.dir.templates'].'/'.$cover);
        }

        $publisher = new PdfWkhtmltopdfPublisher($app);

        $selectedCoverPath = $publisher->getCustomCover();
        $selectedCover = str_replace($app['publishing.dir.templates'].'/', '', $selectedCoverPath);

        $this->assertEquals($coverThatShouldBeUsed, $selectedCover);

        $app['filesystem']->remove($app['publishing.dir.templates']);
    }

    public function provideCoverSampleData()
    {
        return array(
            array(
                array('print/cover.pdf', 'pdf/cover.pdf', 'cover.pdf'),
                'print/cover.pdf',
            ),
            array(
                array('print/cover.jpg', 'pdf/cover.pdf', 'cover.pdf'),
                'pdf/cover.pdf',
            ),
            array(
                array('print/cover.jpg', 'pdf/cover.png', 'cover.pdf'),
                'cover.pdf',
            ),
            array(
                array('pdf/cover.pdf', 'cover.pdf'),
                'pdf/cover.pdf',
            ),
            array(
                array('print/cover.pdf'),
                'print/cover.pdf',
            ),
            array(
                array('pdf/cover.pdf'),
                'pdf/cover.pdf',
            ),
            array(
                array('cover.pdf'),
                'cover.pdf',
            ),
            array(
                array('print/cover.png'),
                '',
            ),
        );
    }

    public function testOneSidedPrintedBookDontIncludeBlankPages()
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

    public function testTwoSidedPrintedBookIncludeBlankPages()
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

    private function getBookConfig($twoSided)
    {
        return array(
            'book' => array(
                'language' => 'en',
                'editions' => array(
                    'print' => array(
                        'format' => 'pdf',
                        'theme' => 'clean',
                        'two_sided' => $twoSided,
                    ),
                ),
            ),
        );
    }

    public function testAddBookCover()
    {
        $app = new Application();

        $tmpDir = $app['app.dir.cache'].'/'.uniqid('phpunit_');
        $app['filesystem']->mkdir($tmpDir);

        $coverFilePath = $tmpDir.'/cover.pdf';
        $bookFilePath = $tmpDir.'/book.pdf';

        $this->createPdfFile($coverFilePath, 'EASYBOOK COVER');
        $this->createPdfFile($bookFilePath, 'easybook contents');

        $publisher = new PdfWkhtmltopdfPublisher($app);
        $publisher->addBookCover($bookFilePath, $coverFilePath);

        $resultingPdfBook = PdfDocument::load($bookFilePath);

        $this->assertCount(2, $resultingPdfBook->pages,
            'The cover page has been added to the book.');

        $this->assertFileExists($coverFilePath,
            'The cover PDF file is NOT deleted after adding it to the book.');

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

    private function createPdfFile($filePath, $contents)
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
