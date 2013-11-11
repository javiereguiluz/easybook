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

use Symfony\Component\Yaml\Yaml;
use Easybook\Tests\TestCase;
use Easybook\DependencyInjection\Application;

class ApplicationTest extends TestCase
{
    public function testSlugify()
    {
        $app = new Application();

        // don't use a dataProvider because it interferes with the slug generation
        $slugs = array(
            array('Lorem ipsum dolor sit amet',      'lorem-ipsum-dolor-sit-amet'),
            array('Lorem ipsum !! dolor sit amet',   'lorem-ipsum-dolor-sit-amet'),
            array('Lorem ipsum + dolor * sit amet',  'lorem-ipsum-dolor-sit-amet'),
            array('Ut enim ad minim veniam',         'ut-enim-ad-minim-veniam'),
            array('Ut enim ad / minim || veniam',    'ut-enim-ad-minim-veniam'),
            array('Ut enim _ad minim_ veniam',       'ut-enim-ad-minim-veniam'),
            array('Lorem ipsum dolor sit amet',      'lorem-ipsum-dolor-sit-amet'),
            array('Lorem ipsum dolor ++ sit amet',   'lorem-ipsum-dolor-sit-amet'),
            array('Ut * enim * ad * minim * veniam', 'ut-enim-ad-minim-veniam'),
            array('Ut enim ad minim veniam',         'ut-enim-ad-minim-veniam'),
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
            array('Lorem ipsum dolor sit amet',      'lorem-ipsum-dolor-sit-amet'),
            array('Lorem ipsum !! dolor sit amet',   'lorem-ipsum-dolor-sit-amet-2'),
            array('Lorem ipsum + dolor * sit amet',  'lorem-ipsum-dolor-sit-amet-3'),
            array('Ut enim ad minim veniam',         'ut-enim-ad-minim-veniam'),
            array('Ut enim ad / minim || veniam',    'ut-enim-ad-minim-veniam-2'),
            array('Ut enim _ad minim_ veniam',       'ut-enim-ad-minim-veniam-3'),
            array('Lorem ipsum dolor sit amet',      'lorem-ipsum-dolor-sit-amet-4'),
            array('Lorem ipsum dolor ++ sit amet',   'lorem-ipsum-dolor-sit-amet-5'),
            array('Ut * enim * ad * minim * veniam', 'ut-enim-ad-minim-veniam-4'),
            array('Ut enim ad minim veniam',         'ut-enim-ad-minim-veniam-5'),
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
        $app['publishing.book.config'] = array(
            'book' => array(
                'title' => 'The title of the book'
            )
        );

        $this->assertEquals('The title of the book', $app->book('title'));

        $newBookTitle = 'The book title set via the method shortcut';
        $app->book('title', $newBookTitle);

        $this->assertEquals($newBookTitle, $app->book('title'));
    }

    public function testEditionMethodShortcut()
    {
        $app = new Application();

        // needed to simulate the third-party library required to publish PDF books
        $app['prince.path'] = __FILE__;

        $app['publishing.edition'] = 'my_edition';

        $app['publishing.book.config'] = array(
            'book' => array(
                'editions' => array(
                    'my_edition' => array(
                        'format' => 'pdf'
                    )
                )
            )
        );

        $this->assertEquals('pdf', $app->edition('format'));

        $app->edition('format', 'epub');
        $this->assertEquals('epub', $app->edition('format'));
    }

    public function testDeprecatedPublishingIdProperty()
    {
        $app = new Application();
        $app['publishing.edition.id'] = 'custom_edition_id';

        try {
            $id = $app['publishing.id'];
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

        // needed to simulate the third-party libraries required
        // to publish PDF and MOBI books
        $app['kindlegen.path'] = __FILE__;
        $app['prince.path']    = __FILE__;

        $app['publishing.edition'] = 'my_edition';

        $app['publishing.book.config'] = array(
            'book' => array(
                'editions' => array(
                    'my_edition' => array(
                        'format' => $outputformat
                    )
                )
            )
        );

        $this->assertInstanceOf($publisherClassName, $app['publisher']);
    }

    public function getPublishers()
    {
        return array(
            array('epub',         'Easybook\Publishers\Epub2Publisher'),
            array('mobi',         'Easybook\Publishers\MobiPublisher'),
            array('pdf',          'Easybook\Publishers\PdfPublisher'),
            array('html',         'Easybook\Publishers\HtmlPublisher'),
            array('html_chunked', 'Easybook\Publishers\HtmlChunkedPublisher'),
        );
    }

    public function testUnsupportedPublisher()
    {
        $app = new Application();
        $app['publishing.edition'] = 'my_edition';

        $app['publishing.book.config'] = array(
            'book' => array(
                'editions' => array(
                    'my_edition' => array(
                        'format' => 'this_format_does_not_exist'
                    )
                )
            )
        );

        try {
            $publisher = $app['publisher'];
        } catch (\RuntimeException $e) {
            $this->assertContains('Unknown "this_format_does_not_exist" format', $e->getMessage());
        }
    }

    public function testUnsupportedContentFormat()
    {
        $app = new Application();
        $app['publishing.active_item'] = array(
            'config' => array(
                'format'  => 'this_format_does_not_exist',
                'content' => 'test_chapter'
            )
        );

        try {
            $parser = $app['parser'];
        } catch (\RuntimeException $e) {
            $this->assertContains('(easybook only supports Markdown)', $e->getMessage());
        }
    }

    public function testGetTitleMethodForDefaultTitles()
    {
        $app = new Application();

        $files = $app['finder']->files()->name('titles.*.yml')
                               ->in($app['app.dir.translations']);

        foreach ($files as $file) {
            $locale = substr($file->getRelativePathname(), -6, 2);

            // reset the application for each language because titles are cached
            $app = new Application();
            $app['publishing.edition'] = 'edition1';
            $app['publishing.book.config'] = array('book' => array(
                'language' => $locale,
                'editions' => array(
                    'edition1' => array(),
                ),
            ));

            $titles = Yaml::parse($file->getPathname());
            foreach ($titles['title'] as $key => $expectedValue) {
                $this->assertEquals($expectedValue, $app->getTitle($key));
            }
        }
    }

    public function testGetLabelMethodForDefaultLabels()
    {
        $app = new Application();

        $files = $app['finder']->files()->name('labels.*.yml')
                               ->in($app['app.dir.translations']);

        $labelVariables = array(
            'item' => array(
                'number'   => 1,
                'counters' => array(1, 1, 1, 1, 1, 1),
                'level'    => 1
            ),
            'element' => array(
                'number' => 1
            )
        );

        foreach ($files as $file) {
            $locale = substr($file->getRelativePathname(), -6, 2);

            // reset the application for each language because labels are cached
            $app = new Application();
            $app['publishing.edition'] = 'edition1';
            $app['publishing.book.config'] = array('book' => array(
                'language' => $locale,
                'editions' => array(
                    'edition1' => array(),
                ),
            ));

            $labels = Yaml::parse($file->getPathname());
            foreach ($labels['label'] as $key => $value) {
                // some labels (chapter and appendix) are arrays instead of strings
                if (is_array($value)) {
                    foreach ($value as $i => $subLabel) {
                        $expectedValue = $app->renderString($subLabel, $labelVariables);
                        $labelVariables['item']['level'] = $i+1;

                        $this->assertEquals($expectedValue, $app->getLabel($key, $labelVariables));
                    }
                } else {
                    $expectedValue = $app->renderString($value, $labelVariables);

                    $this->assertEquals($expectedValue, $app->getLabel($key, $labelVariables));
                }
            }
        }
    }

    public function testGetPublishingEditionId()
    {
        // get the ID of a ISBN-less book
        $app = new Application();
        $app['publishing.edition'] = 'edition1';
        $app['publishing.book.config'] = array('book' => array(
            'editions' => array(
                'edition1' => array(),
            ),
        ));

        $publishingId = $app['publishing.edition.id'];

        $this->assertEquals('URN', $publishingId['scheme']);
        $this->assertEquals(36, strlen($publishingId['value']));
        $this->assertRegExp('/[a-f0-9\-]*/', $publishingId['value']);

        // get the ID of a book with an ISBN
        $app = $this->getMock('Easybook\DependencyInjection\Application', array('edition'));
        $app->expects($this->once())
            ->method('edition')
            ->with($this->equalTo('isbn'))
            ->will($this->returnValue('9782918390060'));

        $publishingId = $app['publishing.edition.id'];

        $this->assertEquals('isbn', $publishingId['scheme']);
        $this->assertEquals('9782918390060', $publishingId['value']);
    }

    /**
     * @dataProvider getValuesForGetAndSetMethods
     */
    public function testGetMethod($key, $expectedValue)
    {
        $app = new Application();
        $app[$key] = $expectedValue;

        $this->assertEquals($expectedValue, $app->get($key));
    }

    /**
     * @dataProvider getValuesForGetAndSetMethods
     */
    public function testSetMethod($key, $expectedValue)
    {
        $app = new Application();

        $app->set($key, $expectedValue);
        $this->assertEquals($expectedValue, $app[$key]);
    }

    public function getValuesForGetAndSetMethods()
    {
        return array(
            array('key1', null),
            array('key2', true),
            array('key3', 'string'),
            array('key4', 3),
            array('key5', 3.141592),
            array('key6', array(1, 2, 3))
        );
    }
}