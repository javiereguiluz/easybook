<?php

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Easybook\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EasybookVersionCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setDefinition(array())
            ->setName('version')
            ->setDescription('Shows installed easybook version')
            ->setHelp('The <info>version</info> command shows you the installed version of <info>easybook</info>');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(array(
            '',
            $this->app['app.signature'],
            ' <info>easybook</info> installed version: '
            .'<comment>'.$this->app->getVersion().'</comment>',
            '',
        ));
    }
}
