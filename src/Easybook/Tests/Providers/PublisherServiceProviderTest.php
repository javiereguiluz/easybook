<?php

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Easybook\Tests\Providers;

use Easybook\DependencyInjection\Application;
use Easybook\Providers\PublisherServiceProvider;

class PublisherServiceProviderTest extends \PHPUnit_Framework_TestCase
{

    public function testPdfPublisherWithDefaultEngine()
    {
        // if running in Travis-CI, external utilities will be unavailable
        if ('true' === getenv('TRAVIS')) {
            $this->markTestSkipped("Detected Travis-CI build, skipping test");

            return;
        }

        $app = new Application();
        $app['publishing.book.config'] = $this->getBookConfigForPdfFormat();
        $app['publishing.edition'] = 'print';

        $provider = new PublisherServiceProvider();
        $provider->register($app);

        $this->assertInstanceOf('Easybook\Publishers\PdfPrinceXmlPublisher', $app['publisher']);
    }

    public function testPdfPublisherWithPrinceXmlEngine()
    {
        // if running in Travis-CI, external utilities will be unavailable
        if ('true' === getenv('TRAVIS')) {
            $this->markTestSkipped("Detected Travis-CI build, skipping test");

            return;
        }

        $app = new Application();
        $app['publishing.book.config'] = $this->getBookConfigForPdfFormat('princexml');
        $app['publishing.edition'] = 'print';

        $provider = new PublisherServiceProvider();
        $provider->register($app);

        $this->assertInstanceOf('Easybook\Publishers\PdfPrinceXmlPublisher', $app['publisher']);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testPdfPublisherWithInvalidEngine()
    {
        $app = new Application();
        $app['publishing.book.config'] = $this->getBookConfigForPdfFormat('invalid_engine');
        $app['publishing.edition'] = 'print';

        $provider = new PublisherServiceProvider();
        $provider->register($app);

        $this->assertInstanceOf('Easybook\Publishers\PdfWkhtmltopdfPublisher', $app['publisher']);
    }

    public function testPdfPublisherWithWkhtmltopdfEngine()
    {
        // if running in Travis-CI, external utilities will be unavailable
        if ('true' === getenv('TRAVIS')) {
            $this->markTestSkipped("Detected Travis-CI build, skipping test");

            return;
        }

        $app = new Application();
        $app['publishing.book.config'] = $this->getBookConfigForPdfFormat('wkhtmltopdf');
        $app['publishing.edition'] = 'print';

        $app->register(new PublisherServiceProvider());

        $this->assertInstanceOf('Easybook\Publishers\PdfWkhtmltopdfPublisher', $app['publisher']);
    }

    private function getBookConfigForPdfFormat($pdfEngine = null)
    {
        $book = array(
            'book' => array(
                'language' => 'en',
                'editions' => array(
                    'print' => array(
                        'format' => 'pdf'
                    ),
                ),
            ),
        );

        if ($pdfEngine) {
            $book['book']['editions']['print']['pdf_engine'] = $pdfEngine;
        }

        return $book;
    }
}