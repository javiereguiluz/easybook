<?php declare(strict_types=1);

namespace Easybook\Templating;

use RuntimeException;

final class Renderer
{
    public function __construct()
    {
    }

    /**
     * Renders any template (currently only supports Twig templates).
     *
     * @param string $template   The template name (it can include a namespace)
     * @param array  $variables  Optional variables passed to the template
     * @param string $targetFile Optional output file path. If set, the rendered
     *                           template is saved in this file.
     *
     * @return string The result of rendering the Twig template
     *
     *  @throws \RuntimeException  If the given template is not a Twig template
     */
    public function render(string $template, array $variables = [], string $targetFile = null): string
    {
        if (substr($template, -5) !== '.twig') {
            throw new RuntimeException(sprintf(
                'Unsupported format for "%s" template (easybook only supports Twig)',
                $template
            ));
        }
        $rendered = $this['twig']->render($template, $variables);
        if ($targetFile !== null) {
            if (! is_dir($dir = dirname($targetFile))) {
                $this['filesystem']->mkdir($dir);
            }
            file_put_contents($targetFile, $rendered);
        }
        return $rendered;
    }
}
