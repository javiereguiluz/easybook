<?php

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Easybook\Tests;

use Easybook\DependencyInjection\ContainerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Container;

abstract class AbstractContainerAwareTestCase extends TestCase
{
    /**
     * @var ContainerInterface|Container
     */
    protected $container;

    protected function setUp()
    {
        $this->container = (new ContainerFactory())->create();
    }
}
