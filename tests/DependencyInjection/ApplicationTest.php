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

use Easybook\Tests\AbstractContainerAwareTestCase;
use Symfony\Component\Yaml\Yaml;

final class ApplicationTest extends AbstractContainerAwareTestCase
{
    public function testSlugify(): void
    {
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

    public function testSlugifyUniquely(): void
    {
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

    /**
     * @dataProvider provideHighlightFixtures
     */
    public function testHighlight($originalFilePath, $highlightedFilePath): void
    {
        // mock the $app object to disable the highlight cache
        $app = $this->getMock('Easybook\DependencyInjection\Application', array('edition'));
        $app->expects($this->any())
            ->method('edition')
            ->will($this->returnValue(null));

        $languageToHighlight = substr(basename($originalFilePath), 0, -4);

        $this->assertEquals(
            file_get_contents($highlightedFilePath),
            $app->highlight(file_get_contents($originalFilePath), $languageToHighlight),
            'Code snippet is highlighted correctly.'
        );
    }

    public function provideHighlightFixtures()
    {
        $fixturesDir = __DIR__.'/fixtures/highlight';

        return array_map(null, glob($fixturesDir.'/input/*.txt'), glob($fixturesDir.'/output/*.txt'));
    }

    public function testBookMethodShortcut(): void
    {
        $app = new Application();
        $app['publishing.book.config'] = array(
            'book' => array(
                'title' => 'The title of the book',
            ),
        );

        $this->assertEquals('The title of the book', $app->book('title'));

        $newBookTitle = 'The book title set via the method shortcut';
        $app->book('title', $newBookTitle);

        $this->assertEquals($newBookTitle, $app->book('title'));
    }

    public function testEditionMethodShortcut(): void
    {
        $app = new Application();

        $app['publishing.edition'] = 'my_edition';

        $app['publishing.book.config'] = array(
            'book' => array(
                'editions' => array(
                    'my_edition' => array(
                        'format' => 'pdf',
                    ),
                ),
            ),
        );

        $this->assertEquals('pdf', $app->edition('format'));

        $app->edition('format', 'epub');
        $this->assertEquals('epub', $app->edition('format'));
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Deprecated
     * @expectedExceptionMessage The "publishing.id" option is deprecated
     */
    public function testDeprecatedPublishingIdProperty(): void
    {
        $app['publishing.edition.id'] = 'custom_edition_id';

        $id = $app['publishing.id'];
    }

    /**
     * @dataProvider getPublishers
     */
    public function testPublisherTypes($outputformat, $publisherClassName): void
    {
        $app['publishing.edition'] = 'my_edition';

        $app['publishing.book.config'] = array(
            'book' => array(
                'editions' => array(
                    'my_edition' => array(
                        'format' => $outputformat,
                    ),
                ),
            ),
        );

        $this->assertInstanceOf($publisherClassName, $app['publisher']);
    }

    public function getPublishers()
    {
        return array(
            array('epub',         'Easybook\Publishers\Epub2Publisher'),
            array('pdf',          'Easybook\Publishers\PdfPublisher'),
            array('html',         'Easybook\Publishers\HtmlPublisher'),
            array('html_chunked', 'Easybook\Publishers\HtmlChunkedPublisher'),
        );
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Unknown "this_format_does_not_exist" format
     */
    public function testUnsupportedPublisher(): void
    {
        $app = new Application();
        $app['publishing.edition'] = 'my_edition';

        $app['publishing.book.config'] = array(
            'book' => array(
                'editions' => array(
                    'my_edition' => array(
                        'format' => 'this_format_does_not_exist',
                    ),
                ),
            ),
        );

        $publisher = $app['publisher'];
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage (easybook only supports Markdown)
     */
    public function testUnsupportedContentFormat(): void
    {
        $app = new Application();
        $app['publishing.active_item'] = array(
            'config' => array(
                'format' => 'this_format_does_not_exist',
                'content' => 'test_chapter',
            ),
        );

        $parser = $app['parser'];
    }

    public function testGetTitleMethodForDefaultTitles(): void
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

    public function testGetLabelMethodForDefaultLabels(): void
    {
        $app = new Application();

        $files = $app['finder']->files()->name('labels.*.yml')
                               ->in($app['app.dir.translations']);

        $labelVariables = array(
            'item' => array(
                'number' => 1,
                'counters' => array(1, 1, 1, 1, 1, 1),
                'level' => 1,
            ),
            'element' => array(
                'number' => 1,
            ),
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
                        $labelVariables['item']['level'] = $i + 1;

                        $this->assertEquals($expectedValue, $app->getLabel($key, $labelVariables));
                    }
                } else {
                    $expectedValue = $app->renderString($value, $labelVariables);

                    $this->assertEquals($expectedValue, $app->getLabel($key, $labelVariables));
                }
            }
        }
    }

    public function testGetPublishingEditionId(): void
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
    public function testGetMethod($key, $expectedValue): void
    {
        $app[$key] = $expectedValue;

        $this->assertEquals($expectedValue, $app->get($key));
    }

    /**
     * @dataProvider getValuesForGetAndSetMethods
     */
    public function testSetMethod($key, $expectedValue): void
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
            array('key6', array(1, 2, 3)),
        );
    }
}
