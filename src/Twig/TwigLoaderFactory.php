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

    /**
     * @var string
     */
    private $bookTemplatesDir;

    public function __construct(string $themesDir, string $bookTemplatesDir)
    {
        $this->themesDir = $themesDir;
        $this->bookTemplatesDir = $bookTemplatesDir;
    }

    public function create(): Twig_LoaderInterface
    {
        $twigLoaderChain = new Twig_Loader_Chain();

        // for absolute paths in code
        $twigLoaderChain->addLoader(new ArrayLoader());

        // for relative paths in templatse
        $filesystemLoader = new FilesystemLoader;

//        $filesystemLoader
                    //        $twigLoaderFilesystem->addPath($baseThemeDir, 'theme_base');


        $twigLoaderChain->addLoader($filesystemLoader);

        return $twigLoaderChain;
    }
}
