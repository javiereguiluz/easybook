<?php declare(strict_types=1);

namespace Easybook\Tests;

use Easybook\DependencyInjection\ContainerFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symplify\PackageBuilder\Parameter\ParameterProvider;

abstract class AbstractContainerAwareTestCase extends TestCase
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var ParameterProvider
     */
    protected $parameterProvider;

    /**
     * @var Finder
     */
    protected $finder;

    /**
     * @param mixed[] $data
     */
    public function __construct(?string $name = null, array $data = [], string $dataName = '')
    {
        $this->container = (new ContainerFactory())->create();
        $this->filesystem = $this->container->get(Filesystem::class);
        $this->finder = $this->container->get(Finder::class);
        $this->parameterProvider = $this->container->get(ParameterProvider::class);

        parent::__construct($name, $data, $dataName);
    }
}
