<?php declare(strict_types=1);

namespace Easybook\DependencyInjection;

use Easybook\DependencyInjection\CompilerPass\CollectorCompilerPass;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symplify\PackageBuilder\DependencyInjection\CompilerPass\AutoBindParametersCompilerPass;
use Symplify\PackageBuilder\DependencyInjection\CompilerPass\AutowireSinglyImplementedCompilerPass;

final class EasybookKernel extends Kernel
{
    /**
     * @var string
     */
    private $config;

    public function __construct()
    {
        parent::__construct('dev', true);
//        // -- labels ---------------------------------------------------------
//        $this['labels'] = function () use ($app) {
//            $labels = Yaml::parse(
//                $app['translations_dir'].'/labels.'.$app->book('language').'.yml'
//            );
//
//            // books can define their own labels files
//            if (null !== $customLabelsFile = $app->getCustomLabelsFile()) {
//                $customLabels = Yaml::parse($customLabelsFile);
//
//                return Toolkit::array_deep_merge_and_replace($labels, $customLabels);
//            }
//
//            return $labels;
//        };
//
//        // -- titles ----------------------------------------------------------
//        $this['titles'] = function () use ($app) {
//            $titles = Yaml::parse(
//                $app['translations_dir'].'/titles.'.$app->book('language').'.yml'
//            );
//
//            // books can define their own titles files
//            if (null !== $customTitlesFile = $app->getCustomTitlesFile()) {
//                $customTitles = Yaml::parse($customTitlesFile);
//
//                return Toolkit::array_deep_merge_and_replace($titles, $customTitles);
//            }
//
//            return $titles;
//        };
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        if ($this->config) {
            $loader->load($this->config);
        }

        $loader->load(__DIR__ . '/../config/config.yml');
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir() . '/_easybook';
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir() . '/_easybook_log';
    }

    /**
     * @return BundleInterface[]
     */
    public function registerBundles(): array
    {
        return [];
    }

    public function bootFromConfig(string $config): void
    {
        $this->config = $config;
        $this->boot();
    }

    protected function prepareContainer(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addCompilerPass(new CollectorCompilerPass());
        $containerBuilder->addCompilerPass(new AutoBindParametersCompilerPass());
        $containerBuilder->addCompilerPass(new AutowireSinglyImplementedCompilerPass());
    }
}
