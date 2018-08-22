<?php declare(strict_types=1);

namespace Easybook\Console;

use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

final class Application extends SymfonyApplication
{
    protected function getDefaultInputDefinition(): InputDefinition
    {
        $defaultInputDefinition = parent::getDefaultInputDefinition();

        $defaultInputDefinition->addOption(new InputOption('configuration', '', InputOption::VALUE_OPTIONAL, 'Additional book configuration options'));

        return $defaultInputDefinition;
    }
}
