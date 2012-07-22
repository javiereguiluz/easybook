<?php

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Easybook\Tests\Configurator;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Easybook\DependencyInjection\Application;
use Easybook\Console\ConsoleApplication;
use Easybook\Tests\TestCase;

class BookConfigurationTest extends TestCase
{
    private $dir;
    private $app;

    public function setUp()
    {
        $this->app = new Application();
        $this->dir = sys_get_temp_dir().'/easybookTests';

        parent::setUp();
    }

    public function tearDown()
    {
        $this->app->get('filesystem')->remove($this->dir);

        parent::tearDown();
    }

    private function publishBookAndCheckParsedConfiguration($options)
    {
        $console = new ConsoleApplication($this->app);

        $sourceDir = __DIR__.'/fixtures/'.$options['slug'];
        $targetDir = $this->dir.'/'.$options['slug'];
        $edition   = $options['edition'];

        // mirror test book contents in temp dir
        $this->app->get('filesystem')->mirror($sourceDir.'/input', $targetDir);

        // rename config_$edition.yml to config.yml
        $this->app->get('filesystem')->copy(
            $targetDir.'/Configuration/config_'.$edition.'.yml',
            $targetDir.'/config.yml',
            true
        );

        // publish the book
        $input = new ArrayInput(array_replace(array(
            'command' => 'publish',
            '--dir'   => $this->dir
        ), $options));
        $console->find('publish')->run($input, new NullOutput());

        $this->assertFileEquals(
            $sourceDir.'/expected/'.$edition.'/book.html',
            $targetDir.'/Output/'.$edition.'/book.html',
            sprintf("'%s' options not properly parsed", $edition)
        );
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Book hasn't defined any edition
     */
    public function testNoConfiguration()
    {
        $this->publishBookAndCheckParsedConfiguration(array(
            'slug'    => 'book2',
            'edition' => 'edition1'
        ));
    }

    public function testNoTitleAndNoAuthor()
    {
        $this->publishBookAndCheckParsedConfiguration(array(
            'slug'    => 'book2',
            'edition' => 'edition2'
        ));
    }

    public function testOnlyTitle()
    {
        $this->publishBookAndCheckParsedConfiguration(array(
            'slug'    => 'book2',
            'edition' => 'edition3'
        ));
    }

    public function testOnlyCustomOptions()
    {
        $this->publishBookAndCheckParsedConfiguration(array(
            'slug'    => 'book2',
            'edition' => 'edition4'
        ));
    }

    public function testAllDefaultOptionsAndSomeCustomOptions()
    {
        $this->publishBookAndCheckParsedConfiguration(array(
            'slug'    => 'book2',
            'edition' => 'edition5'
        ));
    }

    public function testOverrideDefaultOptions()
    {
        $this->publishBookAndCheckParsedConfiguration(array(
            'slug'    => 'book2',
            'edition' => 'edition6'
        ));
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Book hasn't defined any edition
     */
    public function testSimpleButIncompleteDynamicConfiguration()
    {
        $this->publishBookAndCheckParsedConfiguration(array(
            'slug'      => 'book2',
            'edition'   => 'edition7',
            '--configuration' => '{
                "book": {
                    "title": "My dynamic title",
                    "author": "Author Name set from the console"
                }
            }'
        ));
    }

    public function testFullDynamicConfiguration()
    {
        $this->publishBookAndCheckParsedConfiguration(array(
            'slug'      => 'book2',
            'edition'   => 'edition8',
            '--configuration' => '{
                "book": {
                    "title": "My dynamic title",
                    "author": "Author Name set from the console",
                    "editions": {
                        "edition8": null
                    }
                }
            }'
        ));
    }

    public function testDynamicConfigurationOverridesSomeFileOptions()
    {
        $this->publishBookAndCheckParsedConfiguration(array(
            'slug'      => 'book2',
            'edition'   => 'edition9',
            '--configuration' => '{
                "book": {
                    "title": "My dynamic title",
                    "author": "Author Name set from the console",
                    "editions": {
                        "edition9": {
                            "page_size": "US-Letter",
                            "toc": {
                                "deep": 3
                            }
                        }
                    }
                }
            }'
        ));
    }

    public function testDynamicConfigurationSetsNewOptionsAndOverridesFileAndDefaultOptions()
    {
        $this->publishBookAndCheckParsedConfiguration(array(
            'slug'      => 'book2',
            'edition'   => 'edition10',
            '--configuration' => '{
                "book": {
                    "title": "My dynamic title",
                    "generator": {
                        "name": "easybook",
                        "version": "premium"
                    },
                    "contents": [
                        { "element": "cover" },
                        { "element": "toc" }
                    ],
                    "editions": {
                        "edition10": {
                            "extends": "edition9",
                            "page_size": "US-Letter",
                            "price": "15",
                            "pages": 250,
                            "toc": {
                                "deep": 2
                            }
                        }
                    }
                }
            }'
        ));
    }

    public function testConfigurationWithTwigExpressions()
    {
        $this->publishBookAndCheckParsedConfiguration(array(
            'slug'    => 'book2',
            'edition' => 'edition11'
        ));
    }

    public function testDynamicConfigurationWithTwigExpressions()
    {
        $this->publishBookAndCheckParsedConfiguration(array(
            'slug'    => 'book2',
            'edition' => 'edition12',
            '--configuration' => '{
                "book": {
                    "title": "{{ book.buyer }} diary",
                    "buyer": "The name of the buyer",
                    "editions": {
                        "edition12": {
                            "extends": "edition11",
                            "debug": "false",
                            "page_size": "{{ \"A\" ~ (1**2 + 2**1 - 2) }}",
                            "labels": ["chapter"],
                            "toc": {
                                "deep": "{{ book.contents|length }}"
                            }
                        }
                    }
                }
            }'
        ));
    }
}
