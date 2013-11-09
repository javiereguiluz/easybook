<?php

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Easybook\DependencyInjection;

use Easybook\DependencyInjection\Application;

/**
 * Interface implemented by all the easybook service providers.
 */
interface ServiceProviderInterface
{
    /**
     * Registers services on the given service container.
     *
     * Code inspired by Silex\ServiceProviderInterface interface
     * (c) Fabien Potencier <fabien@symfony.com> (MIT license)
     *
     * @param Application $app The object that represents the dependency injection container
     */
    public function register(Application $app);
}
