<?php declare(strict_types=1);

namespace Easybook\Templating;

use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;

final class Renderer
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var Environment
     */
    private $twigEnvironment;

    public function __construct(Filesystem $filesystem, Environment $twigEnvironment)
    {
        $this->filesystem = $filesystem;
        $this->twigEnvironment = $twigEnvironment;
    }

    /**
     * @param mixed[] $variables
     */
    public function render(string $template, array $variables = []): string
    {
        $this->ensureIsTwig($template);

        return $this->twigEnvironment->render($template, $variables);
    }

    /**
     * @param mixed[] $variables
     */
    public function renderToFile(string $template, array $variables, string $targetFile): void
    {
        $this->ensureIsTwig($template);

        $rendered = $this->twigEnvironment->render($template, $variables);
        $this->filesystem->dumpFile($targetFile, $rendered);
    }

    private function ensureIsTwig(string $template): void
    {
        if (substr($template, -5) === '.twig') {
            return;
        }

        throw new RuntimeException(sprintf(
            'Unsupported format for "%s" template (easybook only supports Twig, _s given)',
            $template
        ));
    }
}
