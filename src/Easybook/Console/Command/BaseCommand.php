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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BaseCommand extends Command
{
    protected $app;

    public function getApp()
    {
        return $this->app;
    }

    protected function initialize(InputInterface $input = null, OutputInterface $output = null)
    {
        $this->app = $this->getApplication()->getApp();
    }

    public function asText()
    {
        $app = $this->getApplication()->getApp();
        $txt = $app['app.signature']
               ."\n"
               .parent::asText();

        return $txt;
    }

    public function registerPlugins()
    {
        // register easybook plugins
        $this->registerEventSubscribers($this->app['app.dir.plugins'], 'Easybook\\Plugins');

        // register (optional) custom book plugins
        $this->registerEventSubscribers($this->app['publishing.dir.plugins']);
    }

    private function registerEventSubscribers($dir, $namespace = '')
    {
        if (!file_exists($dir)) {
            return;
        }

        $files = $this->app->get('finder')->files()
            ->name('*Plugin.php')
            ->in($dir)
        ;

        foreach ($files as $file) {
            $className = $file->getBasename('.php');  // strip .php extension

            // book plugins aren't namespaced. We must include the classes.
            if ('' == $namespace) {
                include_once $file->getPathName();
            }

            $r = new \ReflectionClass($namespace.'\\'.$className);
            if ($r->implementsInterface('Symfony\\Component\\EventDispatcher\\EventSubscriberInterface')) {
                $this->app->get('dispatcher')->addSubscriber($r->newInstance());
            }
        }
    }
}