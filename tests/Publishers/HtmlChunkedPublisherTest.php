<?php declare(strict_types=1);

namespace Easybook\Tests\Publishers;

use Easybook\Publishers\HtmlChunkedPublisher;
use Easybook\Tests\AbstractContainerAwareTestCase;
use ReflectionMethod;

final class HtmlChunkedPublisherTest extends AbstractContainerAwareTestCase
{
    /**
     * @var HtmlChunkedPublisher
     */
    private $htmlChunkedPublisher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->htmlChunkedPublisher = $this->container->get(HtmlChunkedPublisher::class);
    }

    public function testNormalizePageNames(): void
    {
        $originalItems = [
            [
                'slug' => 'Lorem ipsum dolor sit amet',
                'label' => 'Chapter 1',
            ],
            [
                'slug' => 'Another lorem ipsum dolor sit amet',
                'label' => 'Appendix A',
            ],
            [
                'slug' => 'Yet another lorem ipsum dolor sit amet',
                'label' => null,
            ],
        ];

        $expectedItems = [
            [
                'slug' => 'Lorem ipsum dolor sit amet',
                'label' => 'Chapter 1',
                'page_name' => 'chapter-1',
            ],
            [
                'slug' => 'Another lorem ipsum dolor sit amet',
                'label' => 'Appendix A',
                'page_name' => 'appendix-a',
            ],
            [
                'slug' => 'Yet another lorem ipsum dolor sit amet',
                'label' => null,
                'page_name' => 'yet-another-lorem-ipsum-dolor-sit-amet',
            ],
        ];

        $method = new ReflectionMethod(HtmlChunkedPublisher::class, 'normalizePageNames');
        $method->setAccessible(true);

        $itemsWithNormalizedPageNames = $method->invoke($this->htmlChunkedPublisher, $originalItems);
        $this->assertSame($expectedItems, $itemsWithNormalizedPageNames);
    }

    /**
     * @dataProvider getFilterBookTocData
     */
    public function testFilterBookToc($maxLevel, $expectedBookToc): void
    {
        $originalBookToc = [
            [
                'slug' => 'item1',
                'level' => 1,
            ],
            [
                'slug' => 'item2',
                'level' => 2,
            ],
            [
                'slug' => 'item3',
                'level' => 3,
            ],
            [
                'slug' => 'item4',
                'level' => 2,
            ],
            [
                'slug' => 'item5',
                'level' => 1,
            ],
        ];

        $method = new ReflectionMethod(HtmlChunkedPublisher::class, 'filterBookToc');
        $method->setAccessible(true);

        $filteredBookToc = $method->invoke($this->htmlChunkedPublisher, $originalBookToc, $maxLevel);
        $this->assertSame($expectedBookToc, $filteredBookToc);
    }

    public function getFilterBookTocData()
    {
        return [
            [0, []],

            [
                1,
                [
                    [
                        'slug' => 'item1',
                        'level' => 1,
                    ],
                    [
                        'slug' => 'item5',
                        'level' => 1,
                    ],
                ],
            ],

            [
                2,
                [
                    [
                        'slug' => 'item1',
                        'level' => 1,
                    ],
                    [
                        'slug' => 'item2',
                        'level' => 2,
                    ],
                    [
                        'slug' => 'item4',
                        'level' => 2,
                    ],
                    [
                        'slug' => 'item5',
                        'level' => 1,
                    ],
                ],
            ],

            [
                3,
                [
                    [
                        'slug' => 'item1',
                        'level' => 1,
                    ],
                    [
                        'slug' => 'item2',
                        'level' => 2,
                    ],
                    [
                        'slug' => 'item3',
                        'level' => 3,
                    ],
                    [
                        'slug' => 'item4',
                        'level' => 2,
                    ],
                    [
                        'slug' => 'item5',
                        'level' => 1,
                    ],
                ],
            ],

            [
                4,
                [
                    [
                        'slug' => 'item1',
                        'level' => 1,
                    ],
                    [
                        'slug' => 'item2',
                        'level' => 2,
                    ],
                    [
                        'slug' => 'item3',
                        'level' => 3,
                    ],
                    [
                        'slug' => 'item4',
                        'level' => 2,
                    ],
                    [
                        'slug' => 'item5',
                        'level' => 1,
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider getFindItemPositionData
     */
    public function testFindItemPosition($expectedPosition, $itemToFind, $criteria): void
    {
        $bookToc = [
            [
                'slug' => 'item1',
                'url' => 'chapter-1/item-1.html',
            ],
            [
                'slug' => 'item2',
                'url' => 'item-2.html',
            ],
            [
                'slug' => 'item3',
                'url' => 'chapter-2/item-3.html',
            ],
            [
                'slug' => 'item1',
                'url' => 'other-chapter-1.html',
            ],
            [
                'slug' => 'item1',
                'url' => 'another-chapter-1.html',
            ],
        ];

        $method = new ReflectionMethod(HtmlChunkedPublisher::class, 'findItemPosition');
        $method->setAccessible(true);

        $this->assertSame(
            $expectedPosition,
            $method->invoke($this->htmlChunkedPublisher, $itemToFind, $bookToc, $criteria)
        );
    }

    public function getFindItemPositionData()
    {
        return [
            [0, ['slug' => 'item1'], 'slug'],
            [1, ['slug' => 'item2'], 'slug'],
            [2, ['slug' => 'item3'], 'slug'],
            [-1, ['slug' => 'item4'], 'slug'],
            [0, ['slug' => 'item1'], 'slug'],
            [-1, ['slug' => 'item1'], 'url'],
            [-1, ['slug' => 'item1'], 'this_criteria_does_not_exist'],
            [0, ['url' => 'chapter-1/item-1.html'], 'url'],
            [1, ['url' => 'item-2.html'], 'url'],
            [2, ['url' => 'chapter-2/item-3.html'], 'url'],
            [3, ['url' => 'other-chapter-1.html'], 'url'],
            [4, ['url' => 'another-chapter-1.html'], 'url'],
        ];
    }

    /**
     * @dataProvider getGetPreviousChunkData
     */
    public function testGetPreviousChunk($currentPosition, $expectedItem): void
    {
        $bookToc = [
            ['slug' => 'item1'],
            ['slug' => 'item2'],
            ['slug' => 'item3'],
            ['slug' => 'item4'],
            ['slug' => 'item5'],
        ];

        $method = new ReflectionMethod(HtmlChunkedPublisher::class, 'getPreviousChunk');
        $method->setAccessible(true);

        $this->assertSame($expectedItem, $method->invoke($this->htmlChunkedPublisher, $currentPosition, $bookToc));
    }

    public function getGetPreviousChunkData()
    {
        return [
            [0, [
                'level' => 1,
                'slug' => 'index',
                'url' => 'index.html',
            ]],
            [1, ['slug' => 'item1']],
            [2, ['slug' => 'item2']],
            [3, ['slug' => 'item3']],
            [4, ['slug' => 'item4']],
            [5, ['slug' => 'item5']],
            [6, [
                'level' => 1,
                'slug' => 'index',
                'url' => 'index.html',
            ]],
        ];
    }

    /**
     * @dataProvider getGetNextChunkData
     */
    public function testGetNextChunk($currentPosition, $expectedItem): void
    {
        $bookToc = [
            ['slug' => 'item1'],
            ['slug' => 'item2'],
            ['slug' => 'item3'],
            ['slug' => 'item4'],
            ['slug' => 'item5'],
        ];

        $method = new ReflectionMethod(HtmlChunkedPublisher::class, 'getNextChunk');
        $method->setAccessible(true);

        $this->assertSame($expectedItem, $method->invoke($this->htmlChunkedPublisher, $currentPosition, $bookToc));
    }

    public function getGetNextChunkData()
    {
        return [
            [-1, ['slug' => 'item1']],
            [0, ['slug' => 'item2']],
            [1, ['slug' => 'item3']],
            [2, ['slug' => 'item4']],
            [3, ['slug' => 'item5']],
            [4, null],
        ];
    }
}
