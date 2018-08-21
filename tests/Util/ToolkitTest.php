<?php declare(strict_types=1);

namespace Easybook\Tests\Util;

use Easybook\Tests\AbstractContainerAwareTestCase;
use Easybook\Util\Toolkit;

final class ToolkitTest extends AbstractContainerAwareTestCase
{
    public function testUuidMethodGeneratesValidRfc4211Ids(): void
    {
        $uuid = Toolkit::uuid();
        $this->assertRegExp('/[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}/', $uuid);
    }

    public function testUuidMethodGeneratesRandomIds(): void
    {
        $uuids = [];
        while (count($uuids) < 1000) {
            $uuids[] = Toolkit::uuid();
        }

        $this->assertSame(count($uuids), count(array_unique($uuids)));
    }

    /**
     * @dataProvider getArrayDeepMergeAndReplaceData
     */
    public function testArrayDeepMergeAndReplace($arguments, $expectedArray): void
    {
        $this->assertSame(
            $expectedArray,
            call_user_func_array('Easybook\Util\Toolkit::array_deep_merge_and_replace', $arguments)
        );
    }

    public function getArrayDeepMergeAndReplaceData()
    {
        return [
            [
                [
                    [
                        'a' => 1,
                        'b' => 2,
                        'c' => 3,
                    ],
                    ['a' => 10],
                ],
                [
                    'a' => 10,
                    'b' => 2,
                    'c' => 3,
                ],
            ],

            [
                [
                    [
                        'a' => 1,
                        'b' => 2,
                        'c' => 3,
                    ],
                    ['a' => 10],
                    [
                        'a' => 100,
                        'c' => 300,
                    ],
                ],
                [
                    'a' => 100,
                    'b' => 2,
                    'c' => 300,
                ],
            ],

            [
                [
                    ['a' => [
                        'b' => 2,
                        'c' => 3,
                    ]],
                    ['a' => 10],
                ],
                ['a' => 10],
            ],

            [
                [
                    ['a' => [
                        'b' => 2,
                        'c' => 3,
                    ]],
                    ['a' => ['b' => 20]],
                    ['a' => ['c' => 300]],
                ],
                ['a' => [
                    'b' => 20,
                    'c' => 300,
                ]],
            ],

            [
                [
                    ['a' => [
                        'b' => 2,
                        'c' => 3,
                    ]],
                    ['a' => ['b' => ['c' => 30]]],
                    ['a' => ['b' => ['d' => 400]]],
                ],
                ['a' => [
                    'b' => [
                        'c' => 30,
                        'd' => 400,
                    ],
                    'c' => 3,
                ]],
            ],

            [
                [
                    ['a' => ['b' => ['c' => ['d' => 4]]]],
                    ['a' => ['b' => ['c' => ['d' => 40]]]],
                    ['a' => ['b' => ['c' => ['d' => 400]]]],
                    ['a' => ['b' => ['c' => ['d' => 4000]]]],
                ],
                ['a' => ['b' => ['c' => ['d' => 4000]]]],
            ],

            [[[1, 2, 3, 4], ['a', 'b', 'c', 'd']], ['a', 'b', 'c', 'd']],

            [[[1, 2, 3, 4], ['a', 'b', 'c', 'd'], ['+', '-', '*', '/']], ['+', '-', '*', '/']],

            [[[['a']], [['b']], [['c']]], [['c']]],

            [[[['a', 'b', 'b']], [['b', 'c', 'a']], [['c', 'b', 'a']]], [['c', 'b', 'a']]],
        ];
    }
}
