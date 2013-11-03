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

use Symfony\Component\Yaml\Yaml;
use Easybook\DependencyInjection\Application;
use Easybook\Tests\TestCase;

class BookConfigurationTest extends TestCase
{
    public function testBookWithNoConfigFile()
    {
        $app = new Application();

        $app['publishing.dir.book'] = uniqid('this-path-does-not-exist');
        $app['publishing.book.slug'] = 'book_with_no_config_file';

        try {
            $app->loadBookConfiguration();
        } catch (\RuntimeException $e) {
            $this->assertInstanceOf('\RuntimeException', $e);
            $this->assertContains("There is no 'config.yml' configuration file", $e->getMessage());
        }
    }

    /**
     * @dataProvider getStaticTests
     */
    public function testStaticBookConfiguration($testMessage, $commandConfiguration, $bookConfiguration, $expectedConfiguration)
    {
        $app = new Application();

        $configurator = $this->getMock('Easybook\Configurator\BookConfigurator', array('loadBookFileConfiguration'), array($app));
        $configurator->expects($this->once())
            ->method('loadBookFileConfiguration')
            ->will($this->returnValue(Yaml::parse($bookConfiguration) ?: array()))
        ;

        $configuration = $configurator->loadBookConfiguration(null, $commandConfiguration);
        $expectedConfiguration = Yaml::parse($expectedConfiguration);

        $this->assertEquals($expectedConfiguration, $configuration, $testMessage);
    }

    /**
     * @dataProvider getDynamicTests
     */
    public function testDynamicBookConfiguration($testMessage, $commandConfiguration, $bookConfiguration, $expectedConfiguration)
    {
        $app = new Application();

        $configurator = $this->getMock('Easybook\Configurator\BookConfigurator', array('loadBookFileConfiguration'), array($app));
        $configurator->expects($this->once())
            ->method('loadBookFileConfiguration')
            ->will($this->returnValue(Yaml::parse($bookConfiguration) ?: array()))
        ;

        $configuration = $configurator->loadBookConfiguration(null, $commandConfiguration);

        $app['publishing.book.config'] = $configuration;
        $configuration = $configurator->processConfigurationValues();

        $expectedConfiguration = Yaml::parse($expectedConfiguration);
        $this->assertEquals($expectedConfiguration, $configuration, $testMessage);
    }

    public function getStaticTests()
    {
        return $this->getTests(__DIR__.'/fixtures/static');
    }

    public function getDynamicTests()
    {
        return $this->getTests(__DIR__.'/fixtures/dynamic');
    }

    /**
     * code adapted from Twig_Test_IntegrationTestCase class
     * @see http://github.com/fabpot/Twig/blob/master/lib/Twig/Test/IntegrationTestCase.php
     */
    public function getTests($dir)
    {
        $fixturesDir = realpath($dir);
        $tests = array();

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($fixturesDir), \RecursiveIteratorIterator::LEAVES_ONLY) as $file) {
            if (!preg_match('/\.test$/', $file)) {
                continue;
            }

            $test = file_get_contents($file->getRealpath());

            if (preg_match('/--TEST--(.*)--COMMAND_CONFIG--(.*)--BOOK_CONFIG--(.*)--EXPECT--(.*)/sx', $test, $matches)) {
                $testMessage           = trim($matches[1]);
                $commandConfiguration  = trim($matches[2]);
                $bookConfiguration     = trim($matches[3]);
                $expectedConfiguration = trim($matches[4]);
            } else {
                throw new \InvalidArgumentException(sprintf('Test "%s" is not valid.', str_replace($fixturesDir.'/', '', $file)));
            }

            $tests[] = array($testMessage, $commandConfiguration, $bookConfiguration, $expectedConfiguration);
        }

        return $tests;
    }
}
