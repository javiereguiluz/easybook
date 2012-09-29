<?php

namespace Easybook\Generator;

abstract class Generator
{
    abstract public function generate();

    protected function render($skeletonDir, $template, $parameters)
    {
        $loader = new \Twig_Loader_Filesystem($skeletonDir);
        $twig = new \Twig_Environment($loader, array(
            'debug' => true,
            'cache' => false,
            'strict_variables' => true,
            'autoescape' => false,
        ));

        return $twig->render($template, $parameters);
    }

    protected function renderFile($skeletonDir, $template, $target, $parameters)
    {
        if (!is_dir(dirname($target))) {
            mkdir(dirname($target), 0777, true);
        }

        return file_put_contents($target, $this->render($skeletonDir, $template, $parameters));
    }
}