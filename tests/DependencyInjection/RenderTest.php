<?php declare(strict_types=1);

namespace Easybook\Tests\DependencyInjection;

use Easybook\Tests\AbstractContainerAwareTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Twig_Loader_Filesystem;

/**
 * Tests related to the render() method
 */
final class RenderTest extends AbstractContainerAwareTestCase
{
    private $app;

    /**
     * @var Filesystem
     */
    private $filesystem;

    private $templateDir;

    protected function setUp(): void
    {
        // setup temp dir for generated files
        $this->templateDir = $this->app['app.dir.cache'] . '/' . uniqid('phpunit_', true);
        $this->filesystem = new Filesystem();
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
     * @expectedException RuntimeException
     * @expectedExceptionMessage (easybook only supports Twig)
     */
    public function testNonTwigTemplate(): void
    {
        $this->app->render('template.tpl');
    }

    /**
     * @expectedException Twig_Error_Loader
     * @expectedExceptionRegExp /Unable to find template (.*)/
     */
    public function testUndefinedTwigTemplate(): void
    {
        $this->app->render('template.twig');
    }

    public function testSimpleTemplate(): void
    {
        $templateFileName = 'template.twig';

        file_put_contents($this->templateDir . '/' . $templateFileName, 'Template for "{{ book.title }}"');

        $this->assertSame('Template for "Custom Test Book Title"', $this->app->render($templateFileName));
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
            $this->app->render($templateFileName, ['author' => 'easybook tests'])
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

        $this->app->render(
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
