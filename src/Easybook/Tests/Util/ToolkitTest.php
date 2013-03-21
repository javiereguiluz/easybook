<?php

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Easybook\Tests\Util;

use Easybook\Util\Toolkit;
use Easybook\Tests\TestCase;

class ToolkitTest extends TestCase
{
    public function testUuidMethodGeneratesValidRfc4211Ids()
    {
        $uuid = Toolkit::uuid();
        $this->assertRegExp(
            '/[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}/',
            $uuid
        );
    }

    public function testUuidMethodGeneratesRandomIds()
    {
        $uuids = array();
        while (count($uuids) < 1000) {
            $uuids[] = Toolkit::uuid();
        }

        $this->assertEquals(count($uuids), count(array_unique($uuids)));
    }

    /**
     * @dataProvider getArrayDeepMergeAndReplaceData
     */
    public function testArrayDeepMergeAndReplace($arguments, $expectedArray)
    {
        $this->assertEquals(
            $expectedArray,
            call_user_func_array('Easybook\Util\Toolkit::array_deep_merge_and_replace', $arguments));
    }

    public function getArrayDeepMergeAndReplaceData()
    {
        return array(
            array(
                array(
                    array('a' => 1, 'b' => 2, 'c' => 3),
                    array('a' => 10),
                ),
                array('a' => 10, 'b' => 2, 'c' => 3)
            ),

            array(
                array(
                    array('a' => 1, 'b' => 2, 'c' => 3),
                    array('a' => 10),
                    array('a' => 100, 'c' => 300)
                ),
                array('a' => 100, 'b' => 2, 'c' => 300)
            ),

            array(
                array(
                    array('a' => array('b' => 2, 'c' => 3)),
                    array('a' => 10)
                ),
                array('a' => 10)
            ),

            array(
                array(
                    array('a' => array('b' => 2, 'c' => 3)),
                    array('a' => array('b' => 20)),
                    array('a' => array('c' => 300)),
                ),
                array('a' => array('b' => 20, 'c' => 300))
            ),

            array(
                array(
                    array('a' => array('b' => 2, 'c' => 3)),
                    array('a' => array('b' => array('c' => 30))),
                    array('a' => array('b' => array('d' => 400))),
                ),
                array('a' => array('b' => array('c' => 30, 'd' => 400), 'c' => 3))
            ),

            array(
                array(
                    array('a' => array('b' => array('c' => array('d' => 4)))),
                    array('a' => array('b' => array('c' => array('d' => 40)))),
                    array('a' => array('b' => array('c' => array('d' => 400)))),
                    array('a' => array('b' => array('c' => array('d' => 4000)))),
                ),
                array('a' => array('b' => array('c' => array('d' => 4000)))),
            ),

            array(
                array(
                    array(1, 2, 3, 4),
                    array('a', 'b', 'c', 'd'),
                ),
                array('a', 'b', 'c', 'd'),
            ),

            array(
                array(
                    array(1, 2, 3, 4),
                    array('a', 'b', 'c', 'd'),
                    array('+', '-', '*', '/'),
                ),
                array('+', '-', '*', '/'),
            ),

            array(
                array(
                    array(array('a')),
                    array(array('b')),
                    array(array('c')),
                ),
                array(array('c')),
            ),

            array(
                array(
                    array(array('a', 'b', 'b')),
                    array(array('b', 'c', 'a')),
                    array(array('c', 'b', 'a')),
                ),
                array(array('c', 'b', 'a')),
            ),
        );
    }
}