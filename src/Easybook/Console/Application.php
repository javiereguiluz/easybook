<?php declare(strict_types=1);

namespace Easybook\Console;

use Jean85\PrettyVersions;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

final class Application extends SymfonyApplication
{

    public function __construct()
    {
        parent::__construct('easybook', $this->getPrettyVersion());
    }

    protected function getDefaultInputDefinition(): InputDefinition
    {
        $defaultInputDefinition = parent::getDefaultInputDefinition();

        $defaultInputDefinition->addOption(
            new InputOption('configuration', '', InputOption::VALUE_OPTIONAL, 'Additional book configuration options')
        );

        return $defaultInputDefinition;
    }

    private function getPrettyVersion(): string
    {
        $version = PrettyVersions::getVersion('easybook/easybook');

        return $version->getPrettyVersion();
    }
}
