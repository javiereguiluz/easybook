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
use Easybook\Plugins\FixPrinceFootnotesPlugin;
use Easybook\Publishers\HtmlPublisher;
use Easybook\Publishers\PdfPublisher;
use Easybook\Tests\TestCase;

class FixPrinceFootnotesPluginTest extends TestCase
{
    /**
     * Convert footnote markup
     *
     * @dataProvider getTestFootnotePluginData
     */
    public function testFootnotePlugin($inputFilePath, $expectedFilePath)
    {
        $fixturesDir = __DIR__.'/fixtures/footnotes/';

        $app = new Application();
        $app['publisher'] = $app->share(function ($app) {
             return new PdfPublisher($app);
         });

        $event  = new ParseEvent($app);
        $plugin = new FixPrinceFootnotesPlugin();

        $event->setItem(array(
            'content' => file_get_contents($fixturesDir.'/'.$inputFilePath)
        ));

        $plugin->onItemPostParse($event);
        $item = $event->getItem();

        $this->assertEquals(
            file_get_contents($fixturesDir.'/'.$expectedFilePath),
            $item['content']
        );
    }

    /**
     * Be sure that we only change item if the PdfPublisher is used.
     *
     * @dataProvider getTestFootnotePluginData
     */
    public function testOnlyApplyWithPdfPublisher($inputFilePath, $expectedFilePath)
    {
        $fixturesDir = __DIR__.'/fixtures/footnotes/';

        $app = new Application();
        $app['publisher'] = $app->share(function ($app) {
             return new HtmlPublisher($app);
         });

        $event  = new ParseEvent($app);
        $plugin = new FixPrinceFootnotesPlugin();

        $event->setItem(array(
            'content' => file_get_contents($fixturesDir.'/'.$inputFilePath)
        ));

        $plugin->onItemPostParse($event);
        $item = $event->getItem();

        $this->assertEquals(
            file_get_contents($fixturesDir.'/'.$inputFilePath),
            $item['content']
        );
    }

    public function getTestFootnotePluginData()
    {
        return array(

            array(
                'input_1.html',
                'expected_1.html',
            ),

        );
    }
}
