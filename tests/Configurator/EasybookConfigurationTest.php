<?php declare(strict_types=1);

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Easybook\Tests\Configurator;

use Easybook\Configurator\BookConfigurator;
use Easybook\Tests\AbstractContainerAwareTestCase;
use Easybook\Util\Toolkit;
use Symfony\Component\Yaml\Yaml;

final class EasybookConfigurationTest extends AbstractContainerAwareTestCase
{
    private $fixturesDir;

    private $app;

    protected function setup(): void
    {
        $this->fixturesDir = __DIR__ . '/fixtures/easybook_configuration';
    }

    /**
     * @dataProvider getConfigFileName
     */
    public function testEasybookConfiguration($configFileName): void
    {
        $this->app = $this->getApplication($configFileName);

        $config = $this->app['configurator']->loadBookFileConfiguration(null);
        $easybookConfig = isset($config['easybook'])
            ? $config['easybook']['parameters']
            : [];

        $expectedConfiguration = Toolkit::array_deep_merge_and_replace(
            $this->getEasybookDefaultParameters(),
            $easybookConfig
        );
        $this->assertEasybookConfiguration($expectedConfiguration);
    }

    public function getConfigFileName()
    {
        return [
            ['no_configuration.yml'],
            ['custom_configuration.yml'],
            ['override_configuration.yml'],
            ['custom_and_override_configuration.yml'],
        ];
    }

    private function assertEasybookConfiguration($expectedConfiguration): void
    {
        foreach ($expectedConfiguration as $option => $expectedValue) {
            $this->assertSame(
                $expectedValue,
                $this->app[$option],
                "\$app['${option}'] = " . (is_array($expectedValue) ? '<Array>' : $expectedValue)
            );
        }
    }

    private function getApplication($configFileName)
    {
        $configurator = $this->getMock(BookConfigurator::class, ['loadBookFileConfiguration'], [$app]);
        $configurator->expects($this->any())
            ->method('loadBookFileConfiguration')
            ->will($this->returnValue(Yaml::parse($this->fixturesDir . '/' . $configFileName) ?: []));

        $app['configurator'] = $configurator;

        $app->loadEasybookConfiguration();

        return $app;
    }

    private function getEasybookDefaultParameters()
    {
        return [
            'app.debug' => false,
            'app.charset' => 'UTF-8',
            'app.name' => 'easybook',
            'parser.options' => [
                'markdown_syntax' => 'easybook',
                'code_block_type' => 'markdown',
            ],
            'prince.path' => null,
            'prince.default_paths' => [
                '/usr/local/bin/prince',
                '/usr/bin/prince',
                'C:\Program Files\Prince\engine\bin\prince.exe',
            ],
            'kindlegen.path' => null,
            'kindlegen.default_paths' => [
                '/usr/local/bin/kindlegen',
                '/usr/bin/kindlegen',
                'c:\KindleGen\kindlegen',
            ],
            'kindlegen.command_options' => '-c1',
            'slugger.options' => [
                'separator' => '-',
                'prefix' => '',
            ],
        ];
    }
}
