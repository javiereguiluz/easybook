<?php

namespace Easybook\Generator;

/**
 * Generic generator class to render Twig templates and save them as files.
 *
 * Code adapted from the SensioGeneratorBundle/Generator/Generator.php class
 * that can be found at:
 *   https://github.com/sensiolabs/SensioGeneratorBundle/blob/master/Generator/Generator.php
 *
 * Copyright of the original code: (c) Fabien Potencier <fabien@symfony.com>
 * LICENSE of the original code:   MIT License (http://opensource.org/licenses/MIT)
 */
abstract class Generator
{
    /**
     * Generates all the needed resources (usually, files and directories).
     */
    abstract public function generate();

    /**
     * Renders a Twig template with the given parameters.
     *
     * @param $skeletonDir        The directory where the templates are located
     * @param string $template    The name of the template to render
     * @param array  $parameters  The parameters passed to the template
     *
     * @return string The contents of the rendered template
     */
    protected function render($skeletonDir, $template, $parameters)
    {
        $loader = new \Twig_Loader_Filesystem($skeletonDir);
        $twig = new \Twig_Environment($loader, array(
            'debug'            => true,
            'cache'            => false,
            'strict_variables' => true,
            'autoescape'       => false,
        ));

        return $twig->render($template, $parameters);
    }

    /**
     * Renders a Twig template with the given parameters and it stores the
     * result if the given filepath.
     *
     * @param $skeletonDir        The directory where the templates are located
     * @param string $template    The name of the template to render
     * @param string $target      The path of the file where the contents of the parsed
     *                            template are stored
     * @param array  $parameters  The parameters passed to the template
     *
     * @return int The number of bytes that were written to the file, or false on failure.
     */
    protected function renderFile($skeletonDir, $template, $target, $parameters)
    {
        if (!is_dir(dirname($target))) {
            mkdir(dirname($target), 0777, true);
        }

        return file_put_contents($target, $this->render($skeletonDir, $template, $parameters));
    }
}