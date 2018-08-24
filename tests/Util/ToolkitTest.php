<?php declare(strict_types=1);

namespace Easybook\Tests\Util;

use Easybook\Tests\AbstractContainerAwareTestCase;
use Easybook\Util\Toolkit;

final class ToolkitTest extends AbstractContainerAwareTestCase
{
    /**
     * @var Toolkit
     */
    private $toolkit;

    protected function setUp(): void
    {
        $this->toolkit = $this->container->get(Toolkit::class);
    }

    /**
     * @dataProvider getArrayDeepMergeAndReplaceData()
     *
     * @param mixed[] $arguments
     * @param mixed[] $expectedArray
     */
    public function testArrayDeepMergeAndReplace(array $arguments, array $expectedArray): void
    {
        $this->assertSame($expectedArray, $this->toolkit->arrayDeepMergeAndReplace(...$arguments));
    }

    /**
     * @return mixed[]
     */
    public function getArrayDeepMergeAndReplaceData(): array
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
