<?php declare(strict_types=1);

namespace Easybook\Tests\DependencyInjection;

use Easybook\Publishers\Epub2Publisher;
use Easybook\Publishers\HtmlChunkedPublisher;
use Easybook\Publishers\HtmlPublisher;
use Easybook\Publishers\PdfPublisher;
use Easybook\Tests\AbstractContainerAwareTestCase;
use Iterator;
use Symfony\Component\Yaml\Yaml;

final class ApplicationTest extends AbstractContainerAwareTestCase
{
    /**
     * @dataProvider getPublishers
     */
    public function testPublisherTypes($outputformat, $publisherClassName): void
    {
        $app['publishing.edition'] = 'my_edition';

        $app['publishing.book.config'] = [
            'book' => [
                'editions' => [
                    'my_edition' => [
                        'format' => $outputformat,
                    ],
                ],
            ],
        ];

        $this->assertInstanceOf($publisherClassName, $app['publisher']);
    }

    public function getPublishers(): Iterator
    {
        yield ['epub', Epub2Publisher::class];
        yield ['pdf', PdfPublisher::class];
        yield ['html', HtmlPublisher::class];
        yield ['html_chunked', HtmlChunkedPublisher::class];
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Unknown "this_format_does_not_exist" format
     */
    public function testUnsupportedPublisher(): void
    {
        $app['publishing.edition'] = 'my_edition';

        $app['publishing.book.config'] = [
            'book' => [
                'editions' => [
                    'my_edition' => [
                        'format' => 'this_format_does_not_exist',
                    ],
                ],
            ],
        ];

        $publisher = $app['publisher'];
    }

    public function testGetTitleMethodForDefaultTitles(): void
    {
        $files = $this->finder->files()
            ->name('titles.*.yml')
            ->in($app['app.dir.translations'])
            ->getIterator();

        foreach ($files as $file) {
            $locale = substr($file->getRelativePathname(), -6, 2);

            // reset the application for each language because titles are cached
            $app['publishing.edition'] = 'edition1';
            $app['publishing.book.config'] = ['book' => [
                'language' => $locale,
                'editions' => [
                    'edition1' => [],
                ],
            ]];

            $titles = Yaml::parse($file->getPathname());
            foreach ($titles['title'] as $key => $expectedValue) {
                $this->assertSame($expectedValue, $app->getTitle($key));
            }
        }
    }

    public function testGetLabelMethodForDefaultLabels(): void
    {
        $files = $this->finder->files()
            ->name('labels.*.yml')
            ->in($app['app.dir.translations'])
            ->getIterator();

        $labelVariables = [
            'item' => [
                'number' => 1,
                'counters' => [1, 1, 1, 1, 1, 1],
                'level' => 1,
            ],
            'element' => [
                'number' => 1,
            ],
        ];

        foreach ($files as $file) {
            $locale = substr($file->getRelativePathname(), -6, 2);

            // reset the application for each language because labels are cached
            $app['publishing.edition'] = 'edition1';
            $app['publishing.book.config'] = ['book' => [
                'language' => $locale,
                'editions' => [
                    'edition1' => [],
                ],
            ]];

            $labels = Yaml::parse($file->getPathname());
            foreach ($labels['label'] as $key => $value) {
                // some labels (chapter and appendix) are arrays instead of strings
                if (is_array($value)) {
                    foreach ($value as $i => $subLabel) {
                        $expectedValue = $app->renderString($subLabel, $labelVariables);
                        $labelVariables['item']['level'] = $i + 1;

                        $this->assertSame($expectedValue, $app->getLabel($key, $labelVariables));
                    }
                } else {
                    $expectedValue = $app->renderString($value, $labelVariables);

                    $this->assertSame($expectedValue, $app->getLabel($key, $labelVariables));
                }
            }
        }
    }

    public function testGetPublishingEditionId(): void
    {
        // get the ID of a ISBN-less book
        $app['publishing.edition'] = 'edition1';
        $app['publishing.book.config'] = ['book' => [
            'editions' => [
                'edition1' => [],
            ],
        ]];

        $publishingId = $app['publishing.edition.id'];

        $this->assertSame('URN', $publishingId['scheme']);
        $this->assertSame(36, strlen($publishingId['value']));
        $this->assertRegExp('/[a-f0-9\-]*/', $publishingId['value']);

        // get the ID of a book with an ISBN
        $app = $this->getMock('Easybook\DependencyInjection\Application', ['edition']);
        $app->expects($this->once())
            ->method('edition')
            ->with($this->equalTo('isbn'))
            ->will($this->returnValue('9782918390060'));

        $publishingId = $app['publishing.edition.id'];

        $this->assertSame('isbn', $publishingId['scheme']);
        $this->assertSame('9782918390060', $publishingId['value']);
    }
}
