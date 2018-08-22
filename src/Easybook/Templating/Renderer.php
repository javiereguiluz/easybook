<?php declare(strict_types=1);

namespace Easybook\Templating;

use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;

final class Renderer
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
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
     */
    public function render(string $template, array $variables = [], ?string $targetFile = null): string
    {
        if (substr($template, -5) !== '.twig') {
            throw new RuntimeException(sprintf(
                'Unsupported format for "%s" template (easybook only supports Twig)',
                $template
            ));
        }
        $rendered = $this['twig']->render($template, $variables);
        if ($targetFile !== null) {
            $this->filesystem->dumpFile($targetFile, $rendered);
        }

        return $rendered;
    }
}
