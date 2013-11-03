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
use Easybook\Plugins\CodePlugin;
use Easybook\Tests\TestCase;

class CodePluginTest extends TestCase
{
    /**
     * @dataProvider  getCodeBlockConfiguration
     *
     * @param string  $inputFilePath        The contents to be parsed
     * @param string  $expectedFilePath     The expected result of parsing the contents
     * @param string  $codeBlockType        The type of code block used in the content
     * @param boolean $enableCodeHightlight Whether or not code listings should be highlighted
     */
    public function testCodeBlocksTypes($inputFilePath, $expectedFilePath, $codeBlockType, $enableCodeHightlight)
    {
        $fixturesDir = __DIR__.'/fixtures/code/';

        $app    = $this->getApp($codeBlockType, $enableCodeHightlight);
        $plugin = new CodePlugin();
        $event  = new ParseEvent($app);

        $event->setItem(array(
            'config'   => array('format' => 'md'),
            'original' => file_get_contents($fixturesDir.'/'.$inputFilePath),
            'content'  => '',
        ));

        // execute pre-parse method of the plugin
        $plugin->parseCodeBlocks($event);
        $item = $event->getItem();

        // parse the item original content
        $item['content'] = $app['parser']->transform($item['original']);

        // execute post-parse method of the plugin
        $event->setItem($item);
        $plugin->fixParsedCodeBlocks($event);
        $item = $event->getItem();

        $this->assertEquals(
            file_get_contents($fixturesDir.'/'.$expectedFilePath),
            $item['content']
        );
    }

    public function getCodeBlockConfiguration()
    {
        return array(
            array('input_1.md', 'expected_easybook_type_disabled_highlight.html', 'easybook', false),
            array('input_1.md', 'expected_easybook_type_enabled_highlight.html',  'easybook', true),

            array('input_2.md', 'expected_fenced_type_disabled_highlight.html',   'fenced',   false),
            array('input_2.md', 'expected_fenced_type_enabled_highlight.html',    'fenced',   true),

            array('input_3.md', 'expected_github_type_disabled_highlight.html',   'github',   false),
            array('input_3.md', 'expected_github_type_enabled_highlight.html',    'github',   true),
        );
    }

    private function getApp($codeBlockType, $enableCodeHightlight)
    {
        $app = new Application();

        $app['publishing.book.slug']   = 'test_book';
        $app['publishing.edition']     = 'test_edition';
        $app['publishing.book.config'] = array(
            'book' => array(
                'slug'     => 'test_book',
                'language' => 'en',
                'editions' => array(
                    'test_edition' => array(
                        'format'          => 'html',
                        'highlight_cache' => false,
                        'highlight_code'  => $enableCodeHightlight,
                        'theme'           => 'clean',
                    )
                )
            )
        );

        // don't try to optimize the following code or you'll end up
        // with this error: 'Indirect modification of overloaded element'
        $parserOptions = $app['parser.options'];
        $parserOptions['code_block_type'] =  $codeBlockType;
        $app['parser.options'] = $parserOptions;

        return $app;
    }
}