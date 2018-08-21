<?php declare(strict_types=1);

namespace Easybook\Tests\Plugins;

use Easybook\DependencyInjection\Application;
use Easybook\Events\ParseEvent;
use Easybook\Plugins\TablePlugin;
use Easybook\Tests\AbstractContainerAwareTestCase;

final class TablePluginTest extends AbstractContainerAwareTestCase
{
    /**
     * @dataProvider getTestTablePluginData
     */
    public function testTablePlugin($inputFilePath, $expectedFilePath, $itemNumber, $addLabels, $expectedLabels): void
    {
        $fixturesDir = __DIR__ . '/fixtures/tables/';
        $app = new Application();

        $app['publishing.book.slug'] = 'test_book';
        $app['publishing.edition'] = 'test_edition';
        $app['publishing.book.config'] = [
            'book' => [
                'slug' => 'test_book',
                'language' => 'en',
                'editions' => [
                    'test_edition' => [
                        'format' => 'html',
                        'labels' => $addLabels ? ['table'] : [],
                        'theme' => 'clean',
                    ],
                ],
            ],
        ];

        $event = new ParseEvent($app);
        $plugin = new TablePlugin();

        $event->setItem([
            'config' => ['number' => $itemNumber],
            'content' => file_get_contents($fixturesDir . '/' . $inputFilePath),
        ]);

        $plugin->decorateAndLabelTables($event);
        $item = $event->getItem();

        $this->assertSame(file_get_contents($fixturesDir . '/' . $expectedFilePath), $item['content']);

        if (count($app['publishing.list.tables']) > 0) {
            foreach ($app['publishing.list.tables'] as $i => $table) {
                $this->assertRegexp('/<table.*<\/table>/s', $table[$i]['item']['content']);

                if ($addLabels) {
                    $this->assertSame($expectedLabels[$i], $table[$i]['item']['label']);
                }
            }
        }
    }

    public function getTestTablePluginData()
    {
        return [
            [
                'input_1.html',
                'expected_1_1.html',
                1,
                true,
                ['Table 1.1', 'Table 1.2'],
            ],

            [
                'input_1.html',
                'expected_1_2.html',
                2,
                true,
                ['Table 2.1', 'Table 2.2'],
            ],

            [
                'input_1.html',
                'expected_1_1.html',
                1,
                false,
                [],
            ],

            [
                'input_2.html',
                'expected_2.html',
                1,
                false,
                [],
            ],

            [
                'input_3.html',
                'expected_3.html',
                'A',
                true,
                ['Table A.1'],
            ],
        ];
    }
}
