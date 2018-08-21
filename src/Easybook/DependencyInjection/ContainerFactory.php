<?php declare(strict_types=1);

namespace Easybook\DependencyInjection;

use Psr\Container\ContainerInterface;

final class ContainerFactory
{
    public function create(): ContainerInterface
    {
        $kernel = new EasybookKernel();
        $kernel->boot();

        return $kernel->getContainer();
    }
}
