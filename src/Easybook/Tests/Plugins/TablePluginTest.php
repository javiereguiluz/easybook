<?php

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Easybook\Tests\Plugins;

use Easybook\DependencyInjection\Application;
use Easybook\Events\ParseEvent;
use Easybook\Plugins\TablePlugin;
use Easybook\Tests\TestCase;

class TablePluginTest extends TestCase
{
    /**
     * @dataProvider getTestTablePluginData
     */
    public function testTablePlugin($inputFilePath, $expectedFilePath, $itemNumber, $addLabels, $expectedLabels)
    {
        $fixturesDir = __DIR__.'/fixtures/tables/';
        $app = new Application();

        $app['publishing.book.slug'] = 'test_book';
        $app['publishing.edition']   = 'test_edition';
        $app['publishing.book.config'] = array(
            'book' => array(
                'slug'     => 'test_book',
                'language' => 'en',
                'editions' => array(
                    'test_edition' => array(
                        'format' => 'html',
                        'labels' => $addLabels ? array('table') : array(),
                        'theme'  => 'clean',
                    )
                )
            )
        );

        $event  = new ParseEvent($app);
        $plugin = new TablePlugin();

        $event->setItem(array(
            'config'  => array('number' => $itemNumber),
            'content' => file_get_contents($fixturesDir.'/'.$inputFilePath)
        ));

        $plugin->decorateAndLabelTables($event);
        $item = $event->getItem();

        $this->assertEquals(
            file_get_contents($fixturesDir.'/'.$expectedFilePath),
            $item['content']
        );

        if (count($app['publishing.list.tables']) > 0) {
            foreach ($app['publishing.list.tables'] as $i => $table) {
                $this->assertRegexp('/<table.*<\/table>/s', $table[$i]['item']['content']);
                
                if ($addLabels) {
                    $this->assertEquals($expectedLabels[$i], $table[$i]['item']['label']);
                }
            }
        }
    }

    public function getTestTablePluginData()
    {
        return array(
            array(
                'input_1.html',
                'expected_1_1.html',
                1,
                true,
                array('Table 1.1', 'Table 1.2')
            ),

            array(
                'input_1.html',
                'expected_1_2.html',
                2,
                true,
                array('Table 2.1', 'Table 2.2')
            ),

            array(
                'input_1.html',
                'expected_1_1.html',
                1,
                false,
                array()
            ),

            array(
                'input_2.html',
                'expected_2.html',
                1,
                false,
                array()
            ),

            array(
                'input_3.html',
                'expected_3.html',
                'A',
                true,
                array('Table A.1')
            ),
        );
    }
}