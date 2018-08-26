<?php declare(strict_types=1);

namespace Easybook\Tests;

use Easybook\DependencyInjection\ContainerFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
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

        // make output silent no to bother test output
        /** @var SymfonyStyle $symfonyStyle */
        $symfonyStyle = $this->container->get(SymfonyStyle::class);
        $symfonyStyle->setVerbosity(OutputInterface::VERBOSITY_QUIET);

        parent::__construct($name, $data, $dataName);
    }
}
