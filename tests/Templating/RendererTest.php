<?php declare(strict_types=1);

namespace Easybook\Tests\Templating;

use Easybook\Templating\Renderer;
use Easybook\Tests\AbstractContainerAwareTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Twig_Loader_Filesystem;

final class RendererTest extends AbstractContainerAwareTestCase
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $templateDir;

    /**
     * @var Renderer
     */
    private $renderer;

    protected function setUp(): void
    {
        $this->filesystem = $this->container->get(Filesystem::class);
        $this->renderer = $this->container->get(Renderer::class);

        // setup temp dir for generated files
        $this->templateDir = $this->container->getParameter('%kernel.cache_dir') . '/' . uniqid('phpunit_', true);
        $this->filesystem->mkdir($this->templateDir);

        $this->app['twig.loader'] = new Twig_Loader_Filesystem($this->templateDir);

        $this->app['publishing.book.config'] = ['book' => [
            'title' => 'Custom Test Book Title',
        ]];
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->templateDir);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage (easybook only supports Twig)
     */
    public function testNonTwigTemplate(): void
    {
        $this->renderer->render('template.tpl');
    }

    /**
     * @expectedException \Twig_Error_Loader
     * @expectedExceptionRegExp /Unable to find template (.*)/
     */
    public function testUndefinedTwigTemplate(): void
    {
        $this->renderer->render('template.twig');
    }

    public function testSimpleTemplate(): void
    {
        $templateFileName = 'template.twig';

        file_put_contents($this->templateDir . '/' . $templateFileName, 'Template for "{{ book.title }}"');

        $this->assertSame('Template for "Custom Test Book Title"', $this->renderer->render($templateFileName));
    }

    public function testTemplateWithCustomVariables(): void
    {
        $templateFileName = 'template.twig';

        file_put_contents(
            $this->templateDir . '/' . $templateFileName,
            'Template for "{{ book.title }}" (by {{ author }})'
        );

        $this->assertSame(
            'Template for "Custom Test Book Title" (by easybook tests)',
            $this->renderer->render($templateFileName, ['author' => 'easybook tests'])
        );
    }

    public function testTemplateRenderedAsAFile(): void
    {
        $templateFileName = 'template.twig';
        $targetFileName = 'rendered.txt';
        $expectedFileName = 'expected.txt';

        file_put_contents(
            $this->templateDir . '/' . $templateFileName,
            'Template for "{{ book.title }}" (by {{ author }})'
        );

        file_put_contents(
            $this->templateDir . '/' . $expectedFileName,
            'Template for "Custom Test Book Title" (by easybook tests)'
        );

        $this->renderer->render(
            $templateFileName,
            ['author' => 'easybook tests'],
            $this->templateDir . '/' . $targetFileName
        );

        $this->assertFileEquals(
            $this->templateDir . '/' . $expectedFileName,
            $this->templateDir . '/' . $targetFileName
        );
    }
}
