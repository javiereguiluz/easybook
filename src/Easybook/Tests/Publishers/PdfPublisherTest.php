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

use Easybook\DependencyInjection\Application;

class PdfPublisherTest extends \PHPUnit_Framework_TestCase
{
    public function testOneSidedPrintedBookDontIncludeBlankPages()
    {
        $app = new Application();
        $app['publishing.book.config'] = $this->getBookConfig(false);
        $app['publishing.edition']     = 'print';

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
        $app['publishing.edition']     = 'print';

        $bookCss = $app->render('@theme/style.css.twig');

        $this->assertContains(
            ".item {\n    page-break-before: right;",
            $bookCss,
            "Two-sided books include blank pages when needed."
        );
    }

    private function getBookConfig($twoSided)
    {
        return array(
            'book' => array(
                'language' => 'en',
                'editions' => array(
                    'print' => array(
                        'format'    => 'pdf',
                        'two_sided' => $twoSided,
                    )
                )
            )
        );
    }
}