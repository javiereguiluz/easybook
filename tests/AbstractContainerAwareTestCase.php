<?php declare(strict_types=1);

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

    /**
     * @param mixed[] $data
     */
    public function __construct(?string $name = null, array $data = [], string $dataName = '')
    {
        $this->container = (new ContainerFactory())->create();

        parent::__construct($name, $data, $dataName);
    }
}
