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

use Easybook\Tests\AbstractContainerAwareTestCase;
use Symfony\Component\Yaml\Yaml;
use Easybook\Util\Toolkit;

final class EasybookConfigurationTest extends AbstractContainerAwareTestCase
{
    private $fixturesDir;
    private $app;

    public function setup()
    {
        $this->fixturesDir = __DIR__.'/fixtures/easybook_configuration';
    }

    /**
     * @dataProvider getConfigFileName
     */
    public function testEasybookConfiguration($configFileName)
    {
        $this->app = $this->getApplication($configFileName);

        $config = $this->app['configurator']->loadBookFileConfiguration(null);
        $easybookConfig = isset($config['easybook'])
            ? $config['easybook']['parameters']
            : array();

        $expectedConfiguration = Toolkit::array_deep_merge_and_replace($this->getEasybookDefaultParameters(), $easybookConfig);
        $this->assertEasybookConfiguration($expectedConfiguration);
    }

    public function getConfigFileName()
    {
        return array(
            array('no_configuration.yml'),
            array('custom_configuration.yml'),
            array('override_configuration.yml'),
            array('custom_and_override_configuration.yml'),
        );
    }

    private function assertEasybookConfiguration($expectedConfiguration)
    {
        foreach ($expectedConfiguration as $option => $expectedValue) {
            $this->assertEquals(
                $expectedValue,
                $this->app[$option],
                "\$app['$option'] = ".(is_array($expectedValue) ? '<Array>' : $expectedValue)
            );
        }
    }

    private function getApplication($configFileName)
    {
        $configurator = $this->getMock('Easybook\Configurator\BookConfigurator', array('loadBookFileConfiguration'), array($app));
        $configurator->expects($this->any())
            ->method('loadBookFileConfiguration')
            ->will($this->returnValue(Yaml::parse($this->fixturesDir.'/'.$configFileName) ?: array()))
        ;

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
