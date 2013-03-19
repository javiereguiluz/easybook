<?php

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Easybook\Tests\DependencyInjection;

use Symfony\Component\Filesystem\Filesystem;
use Easybook\Tests\TestCase;
use Easybook\DependencyInjection\Application;

class ApplicationTest extends TestCase
{
    public function testSlugify()
    {
        $app = new Application();

        // don't use a dataProvider because it interferes with the slug generation
        $slugs = array(
            array('Lorem ipsum dolor sit amet', 'lorem-ipsum-dolor-sit-amet'),
            array('Lorem ipsum !! dolor sit amet', 'lorem-ipsum-dolor-sit-amet'),
            array('Lorem ipsum + dolor * sit amet', 'lorem-ipsum-dolor-sit-amet'),
            array('Ut enim ad minim veniam', 'ut-enim-ad-minim-veniam'),
            array('Ut enim ad / minim || veniam', 'ut-enim-ad-minim-veniam'),
            array('Ut enim _ad minim_ veniam', 'ut-enim-ad-minim-veniam'),
            array('Lorem ipsum dolor sit amet', 'lorem-ipsum-dolor-sit-amet'),
            array('Lorem ipsum dolor ++ sit amet', 'lorem-ipsum-dolor-sit-amet'),
            array('Ut * enim * ad * minim * veniam', 'ut-enim-ad-minim-veniam'),
            array('Ut enim ad minim veniam', 'ut-enim-ad-minim-veniam'),
        );

        foreach ($slugs as $slug) {
            $string = $slug[0];
            $expectedSlug = $slug[1];

            $this->assertEquals($expectedSlug, $app->slugify($string));
        }
    }

    public function testSlugifyUniquely()
    {
        $app = new Application();

        // don't use a dataProvider because it interferes with the slug generation
        $uniqueSlugs = array(
            array('Lorem ipsum dolor sit amet', 'lorem-ipsum-dolor-sit-amet'),
            array('Lorem ipsum !! dolor sit amet', 'lorem-ipsum-dolor-sit-amet-2'),
            array('Lorem ipsum + dolor * sit amet', 'lorem-ipsum-dolor-sit-amet-3'),
            array('Ut enim ad minim veniam', 'ut-enim-ad-minim-veniam'),
            array('Ut enim ad / minim || veniam', 'ut-enim-ad-minim-veniam-2'),
            array('Ut enim _ad minim_ veniam', 'ut-enim-ad-minim-veniam-3'),
            array('Lorem ipsum dolor sit amet', 'lorem-ipsum-dolor-sit-amet-4'),
            array('Lorem ipsum dolor ++ sit amet', 'lorem-ipsum-dolor-sit-amet-5'),
            array('Ut * enim * ad * minim * veniam', 'ut-enim-ad-minim-veniam-4'),
            array('Ut enim ad minim veniam', 'ut-enim-ad-minim-veniam-5'),
        );

        foreach ($uniqueSlugs as $slug) {
            $string = $slug[0];
            $expectedSlug = $slug[1];

            $this->assertEquals($expectedSlug, $app->slugifyUniquely($string));
        }
    }

    public function testHighlight()
    {
        // mock the $app object to disable the highlight cache
        $app = $this->getMock('Easybook\DependencyInjection\Application', array('edition'));
        $app->expects($this->any())
            ->method('edition')
            ->will($this->returnValue(null));

        $fixturesDir = __DIR__.'/fixtures/highlight';

        $this->assertEquals(
            file_get_contents($fixturesDir.'/highlighted_html_snippet.txt'),
            $app->highlight(file_get_contents($fixturesDir.'/raw_html_snippet.txt'), 'html'),
            'HTML code snippet is highlighted correctly.'
        );

        $this->assertEquals(
            file_get_contents($fixturesDir.'/highlighted_php_snippet.txt'),
            $app->highlight(file_get_contents($fixturesDir.'/raw_php_snippet.txt'), 'php'),
            'PHP code snippet is highlighted correctly.'
        );
    }

    public function testBookMethodShortcut()
    {
        $app = new Application();
        $app->set('publishing.book.config', array(
            'book' => array(
                'title' => 'The title of the book'
            )
        ));

        $this->assertEquals('The title of the book', $app->book('title'));

        $newBookTitle = 'The book title set via the method shortcut';
        $app->book('title', $newBookTitle);

        $this->assertEquals($newBookTitle, $app->book('title'));
    }

    public function testEditionMethodShortcut()
    {
        $app = new Application();
        $app->set('publishing.edition', 'my_edition');

        $app->set('publishing.book.config', array(
            'book' => array(
                'editions' => array(
                    'my_edition' => array(
                        'format' => 'pdf'
                    )
                )
            )
        ));

        $this->assertEquals('pdf', $app->edition('format'));

        $app->edition('format', 'epub');
        $this->assertEquals('epub', $app->edition('format'));
    }

    public function testDeprecatedPublishingIdProperty()
    {
        $app = new Application();
        $app->set('publishing.edition.id', 'custom_edition_id');

        try {
            $id = $app->get('publishing.id');
        } catch (\Exception $e) {
            $this->assertInstanceOf('PHPUnit_Framework_Error_Deprecated', $e);
            $this->assertContains('The "publishing.id" option is deprecated', $e->getMessage());
        }
    }

    /**
     * @dataProvider getPublishers
     */
    public function testPublisherTypes($outputformat, $publisherClassName)
    {
        $app = new Application();
        $app->set('publishing.edition', 'my_edition');

        $app->set('publishing.book.config', array(
            'book' => array(
                'editions' => array(
                    'my_edition' => array(
                        'format' => $outputformat
                    )
                )
            )
        ));

        $this->assertInstanceOf($publisherClassName, $app->get('publisher'));
    }

    public function getPublishers()
    {
        return array(
            array('pdf', 'Easybook\Publishers\PdfPublisher'),
            array('html', 'Easybook\Publishers\HtmlPublisher'),
            array('html_chunked', 'Easybook\Publishers\HtmlChunkedPublisher'),
            array('epub', 'Easybook\Publishers\Epub2Publisher'),
            array('epub2', 'Easybook\Publishers\Epub2Publisher'),
        );
    }

    public function testUnsupportedPublisher()
    {
        $app = new Application();
        $app->set('publishing.edition', 'my_edition');

        $app->set('publishing.book.config', array(
            'book' => array(
                'editions' => array(
                    'my_edition' => array(
                        'format' => 'this_format_does_not_exist'
                    )
                )
            )
        ));

        try {
            $publisher = $app->get('publisher');
        } catch (\Exception $e) {
            $this->assertContains('Unknown "this_format_does_not_exist" format', $e->getMessage());
        }
    }

    public function testUnsupportedContentFormat()
    {
        $app = new Application();
        $app->set('publishing.active_item', array(
            'config' => array(
                'format'  => 'this_format_does_not_exist',
                'content' => ''
            )
        ));

        try {
            $$parser = $app->get('parser');
        } catch (\Exception $e) {
            $this->assertContains('(easybook only supports Markdown)', $e->getMessage());
        }
    }
}