<?php declare(strict_types=1);

namespace Easybook\Twig;

use Twig\Loader\ArrayLoader;
use Twig\Loader\FilesystemLoader;
use Twig_Loader_Chain;
use Twig_LoaderInterface;

final class TwigLoaderFactory
{
    /**
     * @var string
     */
    private $themesDir;

    public function __construct(string $themesDir)
    {
        $this->themesDir = $themesDir;
    }

    public function create(): Twig_LoaderInterface
    {
        $twigLoaderChain = new Twig_Loader_Chain();

        // for absolute paths in code
        $twigLoaderChain->addLoader(new ArrayLoader());

        // for relative paths in templates
        $filesystemLoader = new FilesystemLoader();
        $filesystemLoader->addPath($this->themesDir, 'theme_base');

        $twigLoaderChain->addLoader($filesystemLoader);

        return $twigLoaderChain;
    }
}
