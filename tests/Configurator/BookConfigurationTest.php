<?php declare(strict_types=1);

namespace Easybook\Tests\Configurator;

use Easybook\Configurator\BookConfigurator;
use Easybook\Tests\AbstractContainerAwareTestCase;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;

final class BookConfigurationTest extends AbstractContainerAwareTestCase
{
    public function testBookWithNoConfigFile(): void
    {
        $app['publishing.dir.book'] = uniqid('this-path-does-not-exist');
        $app['publishing.book.slug'] = 'book_with_no_config_file';

        try {
            $app->loadBookConfiguration();
        } catch (RuntimeException $e) {
            $this->assertInstanceOf(RuntimeException::class, $e);
            $this->assertContains("There is no 'config.yml' configuration file", $e->getMessage());
        }
    }

    /**
     * @dataProvider getStaticTests()
     */
    public function testStaticBookConfiguration(
        $testMessage,
        $commandConfiguration,
        $bookConfiguration,
        $expectedConfiguration
    ): void {
        $configurator = $this->getMock(BookConfigurator::class, ['loadBookFileConfiguration'], [$app]);
        $configurator->expects($this->once())
            ->method('loadBookFileConfiguration')
            ->will($this->returnValue(Yaml::parse($bookConfiguration) ?: []));

        $configuration = $configurator->loadBookConfiguration(null, $commandConfiguration);
        $expectedConfiguration = Yaml::parse($expectedConfiguration);

        $this->assertSame($expectedConfiguration, $configuration, $testMessage);
    }

    /**
     * @dataProvider getDynamicTests()
     */
    public function testDynamicBookConfiguration(
        $testMessage,
        $commandConfiguration,
        $bookConfiguration,
        $expectedConfiguration
    ): void {
        $configurator = $this->getMock(BookConfigurator::class, ['loadBookFileConfiguration'], [$app]);
        $configurator->expects($this->once())
            ->method('loadBookFileConfiguration')
            ->will($this->returnValue(Yaml::parse($bookConfiguration) ?: []));

        $configuration = $configurator->loadBookConfiguration(null, $commandConfiguration);

        $app['publishing.book.config'] = $configuration;
        $configuration = $configurator->processConfigurationValues();

        $expectedConfiguration = Yaml::parse($expectedConfiguration);
        $this->assertSame($expectedConfiguration, $configuration, $testMessage);
    }

    public function getStaticTests(): array
    {
        return $this->getTests(__DIR__ . '/fixtures/static');
    }

    public function getDynamicTests()
    {
        return $this->getTests(__DIR__ . '/fixtures/dynamic');
    }

    /**
     * code adapted from Twig_Test_IntegrationTestCase class.
     *
     * @see http://github.com/fabpot/Twig/blob/master/lib/Twig/Test/IntegrationTestCase.php
     */
    public function getTests($dir)
    {
        $fixturesDir = realpath($dir);
        $tests = [];

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
            $fixturesDir
        ), RecursiveIteratorIterator::LEAVES_ONLY) as $file) {
            if (! preg_match('/\.test$/', $file)) {
                continue;
            }

            $test = file_get_contents($file->getRealpath());

            if (preg_match(
                '/--TEST--(.*)--COMMAND_CONFIG--(.*)--BOOK_CONFIG--(.*)--EXPECT--(.*)/sx',
                $test,
                $matches
            )) {
                $testMessage = trim($matches[1]);
                $commandConfiguration = trim($matches[2]);
                $bookConfiguration = trim($matches[3]);
                $expectedConfiguration = trim($matches[4]);
            } else {
                throw new InvalidArgumentException(sprintf(
                    'Test "%s" is not valid.',
                    str_replace($fixturesDir . '/', '', $file)
                ));
            }

            $tests[] = [$testMessage, $commandConfiguration, $bookConfiguration, $expectedConfiguration];
        }

        return $tests;
    }
}
