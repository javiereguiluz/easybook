<?php

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Easybook\Tests\DependencyInjection;

use Easybook\Tests\AbstractContainerAwareTestCase;
use Symfony\Component\Filesystem\Filesystem;

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

    public function setUp()
    {
        // setup temp dir for generated files
        $this->templateDir = $this->app['app.dir.cache'].'/'.uniqid('phpunit_', true);
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->templateDir);

        $this->app['twig.loader'] = new \Twig_Loader_Filesystem($this->templateDir);

        $this->app['publishing.book.config'] = array('book' => array(
            'title' => 'Custom Test Book Title',
        ));
    }

    public function tearDown()
    {
        $this->filesystem->remove($this->templateDir);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage (easybook only supports Twig)
     */
    public function testNonTwigTemplate()
    {
        $this->app->render('template.tpl');
    }

    /**
     * @expectedException Twig_Error_Loader
     * @expectedExceptionRegExp /Unable to find template (.*)/
     */
    public function testUndefinedTwigTemplate()
    {
        $this->app->render('template.twig');
    }

    public function testSimpleTemplate()
    {
        $templateFileName = 'template.twig';

        file_put_contents(
            $this->templateDir.'/'.$templateFileName,
            'Template for "{{ book.title }}"'
        );

        $this->assertEquals(
            'Template for "Custom Test Book Title"',
            $this->app->render($templateFileName)
        );
    }

    public function testTemplateWithCustomVariables()
    {
        $templateFileName = 'template.twig';

        file_put_contents(
            $this->templateDir.'/'.$templateFileName,
            'Template for "{{ book.title }}" (by {{ author }})'
        );

        $this->assertEquals(
            'Template for "Custom Test Book Title" (by easybook tests)',
            $this->app->render($templateFileName, array('author' => 'easybook tests'))
        );
    }

    public function testTemplateRenderedAsAFile()
    {
        $templateFileName = 'template.twig';
        $targetFileName = 'rendered.txt';
        $expectedFileName = 'expected.txt';

        file_put_contents(
            $this->templateDir.'/'.$templateFileName,
            'Template for "{{ book.title }}" (by {{ author }})'
        );

        file_put_contents(
            $this->templateDir.'/'.$expectedFileName,
            'Template for "Custom Test Book Title" (by easybook tests)'
        );

        $this->app->render(
            $templateFileName,
            array('author' => 'easybook tests'),
            $this->templateDir.'/'.$targetFileName
        );

        $this->assertFileEquals(
            $this->templateDir.'/'.$expectedFileName,
            $this->templateDir.'/'.$targetFileName
        );
    }
}
