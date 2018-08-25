<?php declare(strict_types=1);

namespace Easybook\Twig;

use Twig_Loader_Chain;
use Twig_LoaderInterface;

final class TwigLoaderFactory
{
    public function create(): Twig_LoaderInterface
    {
        return new Twig_Loader_Chain();
    }
}
