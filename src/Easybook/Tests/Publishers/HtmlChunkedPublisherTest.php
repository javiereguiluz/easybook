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
use Easybook\Publishers\HtmlChunkedPublisher;

class HtmlChunkedPublisherTest extends \PHPUnit_Framework_TestCase
{
    private $app;

    public function setUp()
    {
        $this->app = new Application();
    }

    public function testNormalizePageNames()
    {
        $publisher = new HtmlChunkedPublisher($this->app);

        $originalItems = array(
            array(
                'slug'  => 'Lorem ipsum dolor sit amet',
                'label' => 'Chapter 1',
            ),
            array(
                'slug'  => 'Another lorem ipsum dolor sit amet',
                'label' => 'Appendix A'
            ),
            array(
                'slug'  => 'Yet another lorem ipsum dolor sit amet',
                'label' => null
            )
        );

        $expectedItems = array(
            array(
                'slug'      => 'Lorem ipsum dolor sit amet',
                'label'     => 'Chapter 1',
                'page_name' => 'chapter-1',
            ),
            array(
                'slug'      => 'Another lorem ipsum dolor sit amet',
                'label'     => 'Appendix A',
                'page_name' => 'appendix-a',
            ),
            array(
                'slug'      => 'Yet another lorem ipsum dolor sit amet',
                'label'     => null,
                'page_name' => 'yet-another-lorem-ipsum-dolor-sit-amet',
            )
        );

        $method = new \ReflectionMethod('Easybook\Publishers\HtmlChunkedPublisher', 'normalizePageNames');
        $method->setAccessible(true);

        $itemsWithNormalizedPageNames = $method->invoke($publisher, $originalItems);
        $this->assertEquals($expectedItems, $itemsWithNormalizedPageNames);
    }

    /**
     * @dataProvider getFilterBookTocData
     */
    public function testFilterBookToc($maxLevel, $expectedBookToc)
    {
        $originalBookToc = array(
            array('slug' => 'item1', 'level' => 1),
            array('slug' => 'item2', 'level' => 2),
            array('slug' => 'item3', 'level' => 3),
            array('slug' => 'item4', 'level' => 2),
            array('slug' => 'item5', 'level' => 1),
        );

        $publisher = new HtmlChunkedPublisher($this->app);
        $method = new \ReflectionMethod('Easybook\Publishers\HtmlChunkedPublisher', 'filterBookToc');
        $method->setAccessible(true);

        $filteredBookToc = $method->invoke($publisher, $originalBookToc, $maxLevel);
        $this->assertEquals($expectedBookToc, $filteredBookToc);
    }

    public function getFilterBookTocData()
    {
        return array(
            array(
                0,
                array()
            ),

            array(
                1,
                array(
                    array('slug' => 'item1', 'level' => 1),
                    array('slug' => 'item5', 'level' => 1),
                )
            ),

            array(
                2,
                array(
                    array('slug' => 'item1', 'level' => 1),
                    array('slug' => 'item2', 'level' => 2),
                    array('slug' => 'item4', 'level' => 2),
                    array('slug' => 'item5', 'level' => 1),
                )
            ),

            array(
                3,
                array(
                    array('slug' => 'item1', 'level' => 1),
                    array('slug' => 'item2', 'level' => 2),
                    array('slug' => 'item3', 'level' => 3),
                    array('slug' => 'item4', 'level' => 2),
                    array('slug' => 'item5', 'level' => 1),
                )
            ),

            array(
                4,
                array(
                    array('slug' => 'item1', 'level' => 1),
                    array('slug' => 'item2', 'level' => 2),
                    array('slug' => 'item3', 'level' => 3),
                    array('slug' => 'item4', 'level' => 2),
                    array('slug' => 'item5', 'level' => 1),
                )
            ),
        );
    }

    /**
     * @dataProvider getFindItemPositionData
     */
    public function testFindItemPosition($expectedPosition, $itemToFind, $criteria)
    {
        $publisher = new HtmlChunkedPublisher($this->app);

        $bookToc = array(
            array('slug' => 'item1', 'url' => 'chapter-1/item-1.html'),
            array('slug' => 'item2', 'url' => 'item-2.html'),
            array('slug' => 'item3', 'url' => 'chapter-2/item-3.html'),
            array('slug' => 'item1', 'url' => 'other-chapter-1.html'),
            array('slug' => 'item1', 'url' => 'another-chapter-1.html'),
        );

        $method = new \ReflectionMethod('Easybook\Publishers\HtmlChunkedPublisher', 'findItemPosition');
        $method->setAccessible(true);

        $this->assertEquals(
            $expectedPosition,
            $method->invoke($publisher, $itemToFind, $bookToc, $criteria)
        );
    }

    public function getFindItemPositionData()
    {
        return array(
            array(0, array('slug' => 'item1'), 'slug'),
            array(1, array('slug' => 'item2'), 'slug'),
            array(2, array('slug' => 'item3'), 'slug'),
            array(-1, array('slug' => 'item4'), 'slug'),
            array(0, array('slug' => 'item1'), 'slug'),
            array(-1, array('slug' => 'item1'), 'url'),
            array(-1, array('slug' => 'item1'), 'this_criteria_does_not_exist'),
            array(0, array('url' => 'chapter-1/item-1.html'), 'url'),
            array(1, array('url' => 'item-2.html'), 'url'),
            array(2, array('url' => 'chapter-2/item-3.html'), 'url'),
            array(3, array('url' => 'other-chapter-1.html'), 'url'),
            array(4, array('url' => 'another-chapter-1.html'), 'url'),
        );
    }

    /**
     * @dataProvider getGetPreviousChunkData
     */
    public function testGetPreviousChunk($currentPosition, $expectedItem)
    {
        $publisher = new HtmlChunkedPublisher($this->app);

        $bookToc = array(
            array('slug' => 'item1'),
            array('slug' => 'item2'),
            array('slug' => 'item3'),
            array('slug' => 'item4'),
            array('slug' => 'item5'),
        );

        $method = new \ReflectionMethod('Easybook\Publishers\HtmlChunkedPublisher', 'getPreviousChunk');
        $method->setAccessible(true);

        $this->assertEquals(
            $expectedItem,
            $method->invoke($publisher, $currentPosition, $bookToc)
        );
    }

    public function getGetPreviousChunkData()
    {
        return array(
            array(0, array('level' => 1, 'slug' => 'index', 'url' => 'index.html')),
            array(1, array('slug' => 'item1')),
            array(2, array('slug' => 'item2')),
            array(3, array('slug' => 'item3')),
            array(4, array('slug' => 'item4')),
            array(5, array('slug' => 'item5')),
            array(6, array('level' => 1, 'slug' => 'index', 'url' => 'index.html')),
        );
    }

    /**
     * @dataProvider getGetNextChunkData
     */
    public function testGetNextChunk($currentPosition, $expectedItem)
    {
        $publisher = new HtmlChunkedPublisher($this->app);

        $bookToc = array(
            array('slug' => 'item1'),
            array('slug' => 'item2'),
            array('slug' => 'item3'),
            array('slug' => 'item4'),
            array('slug' => 'item5'),
        );

        $method = new \ReflectionMethod('Easybook\Publishers\HtmlChunkedPublisher', 'getNextChunk');
        $method->setAccessible(true);

        $this->assertEquals(
            $expectedItem,
            $method->invoke($publisher, $currentPosition, $bookToc)
        );
    }

    public function getGetNextChunkData()
    {
        return array(
            array(-1, array('slug' => 'item1')),
            array(0, array('slug' => 'item2')),
            array(1, array('slug' => 'item3')),
            array(2, array('slug' => 'item4')),
            array(3, array('slug' => 'item5')),
            array(4, null),
        );
    }
}