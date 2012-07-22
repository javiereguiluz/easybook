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

class EditionConfigurationTest extends TestCase
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

    public function testNoConfiguration()
    {
        $this->publishBookAndCheckParsedConfiguration(array(
            'slug'    => 'book1',
            'edition' => 'edition1'
        ));
    }

    public function testInheritanceWithNoConfiguration()
    {
        $this->publishBookAndCheckParsedConfiguration(array(
            'slug'    => 'book1',
            'edition' => 'edition2'
        ));
    }

    public function testOverrideSomeDefaultConfiguration()
    {
        $this->publishBookAndCheckParsedConfiguration(array(
            'slug'    => 'book1',
            'edition' => 'edition3'
        ));
    }

    public function testSetAllDefaultConfigurationOptions()
    {
        $this->publishBookAndCheckParsedConfiguration(array(
            'slug'    => 'book1',
            'edition' => 'edition4'
        ));
    }

    public function testDefineOnlyCustomConfigurationOptions()
    {
        $this->publishBookAndCheckParsedConfiguration(array(
            'slug'    => 'book1',
            'edition' => 'edition5'
        ));
    }

    public function testSetAllDefaultConfigurationOptionsAndSomeCustomOptions()
    {
        $this->publishBookAndCheckParsedConfiguration(array(
            'slug'    => 'book1',
            'edition' => 'edition6'
        ));
    }

    public function testSimpleDynamicConfiguration()
    {
        $this->publishBookAndCheckParsedConfiguration(array(
            'slug'      => 'book1',
            'edition'   => 'edition7',
            '--configuration' => '{
                "book": {
                    "editions": {
                        "edition7": {
                            "page_size": "A3"
                        }
                    }
                }
            }'
        ));
    }

    public function testSimpleDynamicConfigurationWithInheritance()
    {
        $this->publishBookAndCheckParsedConfiguration(array(
            'slug'      => 'book1',
            'edition'   => 'edition8',
            '--configuration' => '{
                "book": {
                    "editions": {
                        "edition8": {
                            "page_size": "A3",
                            "toc": {
                                "deep": 3
                            }
                        }
                    }
                }
            }'
        ));
    }

    public function testDynamicConfigurationOverridesConfiguredOptions()
    {
        $this->publishBookAndCheckParsedConfiguration(array(
            'slug'      => 'book1',
            'edition'   => 'edition9',
            '--configuration' => '{
                "book": {
                    "editions": {
                        "edition9": {
                            "include_styles": false,
                            "toc": {
                                "deep": 1
                            }
                        }
                    }
                }
            }'
        ));
    }

    public function testDynamicConfigurationOverridesDefaultOptions()
    {
        $this->publishBookAndCheckParsedConfiguration(array(
            'slug'      => 'book1',
            'edition'   => 'edition10',
            '--configuration' => '{
                "book": {
                    "editions": {
                        "edition10": {
                            "highlight_code": true,
                            "include_styles": false,
                            "isbn": "978-0131103627",
                            "labels": [],
                            "margin": {
                                "outter": "25mm"
                            },
                            "page_size": "US-Letter",
                            "theme": "bright",
                            "toc": {
                                "elements": ["chapter"]
                            },
                            "two_sided": false
                        }
                    }
                }
            }'
        ));
    }

    public function testDynamicConfigurationOverridesCustomOptions()
    {
        $this->publishBookAndCheckParsedConfiguration(array(
            'slug'      => 'book1',
            'edition'   => 'edition11',
            '--configuration' => '{
                "book": {
                    "editions": {
                        "edition11": {
                            "price": 15,
                            "pages": 300,
                            "option2": {
                                "suboption2": "New dynamic value"
                            }
                        }
                    }
                }
            }'
        ));
    }

    public function testDynamicConfigurationOverridesCustomAndDefaultOptions()
    {
        $this->publishBookAndCheckParsedConfiguration(array(
            'slug'      => 'book1',
            'edition'   => 'edition12',
            '--configuration' => '{
                "book": {
                    "editions": {
                        "edition12": {
                            "price": 30,
                            "page_size": "A3",
                            "labels": ["chapter"]
                        }
                    }
                }
            }'
        ));
    }

    public function testConfigurationWithTwigExpressions()
    {
        $this->publishBookAndCheckParsedConfiguration(array(
            'slug'    => 'book1',
            'edition' => 'edition13'
        ));
    }

    public function testDynamicConfigurationWithTwigExpressions()
    {
        $this->publishBookAndCheckParsedConfiguration(array(
            'slug'    => 'book1',
            'edition' => 'edition14',
            '--configuration' => '{
                "book": {
                    "editions": {
                        "edition13": {
                            "debug": "false",
                            "page_size": "{{ \"A\" ~ (1**2 + 2**1 + 1) }}",
                            "labels": ["chapter"]
                        }
                    }
                }
            }'
        ));
    }
}
