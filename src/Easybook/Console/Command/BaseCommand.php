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

/**
 * The class from which any other easybook command extends. It provides
 * a direct access to the dependency injection container represented by
 * the $app variable.
 */
final class BaseCommand extends Command
{
    /**
     * Registers both the built-in easybook plugins and any other
     * custom plugin defined by the book.
     */
    public function registerPlugins()
    {
        // register easybook plugins
        $this->registerEventSubscribers($this->app['app.dir.plugins'], 'Easybook\\Plugins');
    }
}
