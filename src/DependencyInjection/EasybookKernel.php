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
//        dynamic parameters
//        $this['publishing.edition.id'] = function ($app) {
//            if (null !== $isbn = $app->edition('isbn')) {
//                return ['scheme' => 'isbn', 'value' => $isbn];
//            }
//
//            // for ISBN-less books, generate a unique RFC 4211 UUID v4 ID
//            return ['scheme' => 'URN', 'value' => Uuid::uuid4()];
//        };
//
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

//    /**
//     * Shortcut method to get the label of any element type.
//     *
//     * @param string $element   The element type ('chapter', 'foreword', ...)
//     * @param array  $variables Optional variables used to render the label
//     *
//     * @return string The label of the element or an empty string
//     */
//    public function getLabel($element, $variables = array())
//    {
//        $label = isset($this['labels']['label'][$element])
//            ? $this['labels']['label'][$element]
//            : '';
//
//        // some elements (mostly chapters and appendices) have a different label for each level (h1, ..., h6)
//        if (is_array($label)) {
//            $index = $variables['item']['level'] - 1;
//            $label = $label[$index];
//        }
//
//        return $this->renderString($label, $variables);
//    }

//    /**
//     * Shortcut method to get the title of any element type.
//     *
//     * @param string $element The element type ('chapter', 'foreword', ...)
//     *
//     * @return string The title of the element or an empty string
//     */
//    public function getTitle($element)
//    {
//        return isset($this['titles']['title'][$element])
//            ? $this['titles']['title'][$element]
//            : '';
    ////    }
//
//        if (null !== $bookConfig = $this['publishing.book.config']) {
//            $twig->addGlobal('book', $bookConfig['book']);
//
//            $publishingEdition = $this['publishing.edition'];
//            $editions = $this->book('editions');
//            $twig->addGlobal('edition', $editions[$publishingEdition]);
//        }
//
//        return $twig->render($string, $variables);
//    }

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
