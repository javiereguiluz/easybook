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
use Easybook\DependencyInjection\Application;

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

    /**
     * Registers both the built-in easybook plugins and any other
     * custom plugin defined by the book.
     */
    public function registerPlugins()
    {
        // register easybook plugins
        $this->registerEventSubscribers($this->app['app.dir.plugins'], 'Easybook\\Plugins');

        // register (optional) custom book plugins
        $this->registerEventSubscribers($this->app['publishing.dir.plugins']);
    }

    /**
     * It looks for all the event subscribers defined for any of the classes
     * found on the given directory.
     *
     * @param string $dir       The directory where the classes are looked for
     * @param string $namespace The namespace of the classes tha define the event subscribers
     */
    private function registerEventSubscribers($dir, $namespace = '')
    {
        if (!file_exists($dir)) {
            return;
        }

        $files = $this->app['finder']->files()
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
                $this->app['dispatcher']->addSubscriber($r->newInstance());
            }
        }
    }
}
