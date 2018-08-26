<?php declare(strict_types=1);

namespace Easybook\DependencyInjection\CompilerPass;

use Easybook\Console\Command\PublishCommand;
use Easybook\Publishers\PublisherInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symplify\PackageBuilder\DependencyInjection\DefinitionCollector;
use Symplify\PackageBuilder\DependencyInjection\DefinitionFinder;
use Twig\Environment;
use Twig_Extension;

final class CollectorCompilerPass implements CompilerPassInterface
{
    /**
     * @var DefinitionCollector
     */
    private $definitionCollector;

    public function __construct()
    {
        $this->definitionCollector = new DefinitionCollector(new DefinitionFinder());
    }

    public function process(ContainerBuilder $containerBuilder): void
    {
        $this->definitionCollector->loadCollectorWithType(
            $containerBuilder,
            Application::class,
            Command::class,
            'add'
        );

        $this->definitionCollector->loadCollectorWithType(
            $containerBuilder,
            EventDispatcherInterface::class,
            EventSubscriberInterface::class,
            'addSubscriber'
        );

        $this->definitionCollector->loadCollectorWithType(
            $containerBuilder,
            PublishCommand::class,
            PublisherInterface::class,
            'addPublisher'
        );

        $this->definitionCollector->loadCollectorWithType(
            $containerBuilder,
            Environment::class,
            Twig_Extension::class,
            'addExtension'
        );
    }
}
