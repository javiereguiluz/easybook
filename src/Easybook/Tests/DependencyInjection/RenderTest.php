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

use Symfony\Component\Filesystem\Filesystem;
use Easybook\Tests\TestCase;
use Easybook\DependencyInjection\Application;

/**
 * Tests related to the render() method of the
 * Easybook\DependencyInjection\Application class
 */
class RenderTest extends TestCase
{
    protected $app;
    protected $filesystem;
    protected $templateDir;

    public function setUp()
    {
        $this->app = new Application();
        
        // setup temp dir for generated files
        $this->templateDir = $this->app['app.dir.cache'].'/'.uniqid('phpunit_', true);
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->templateDir);

        $this->app['twig.loader'] = new \Twig_Loader_Filesystem($this->templateDir);

        $this->app['publishing.book.config'] = array('book' => array(
            'title' => 'Custom Test Book Title'
        ));
    }

    public function tearDown()
    {
        $this->filesystem->remove($this->templateDir);
    }

    public function testNonTwigTemplate()
    {
        try {
            $this->app->render('template.tpl');
        } catch (\RuntimeException $e) {
            $this->assertInstanceOf('RuntimeException', $e);
            $this->assertContains('(easybook only supports Twig)', $e->getMessage());
        }
    }

    public function testUndefinedTwigTemplate()
    {
        try {
            $this->app->render('template.twig');
        } catch (\Twig_Error_Loader $e) {
            $this->assertInstanceOf('Twig_Error_Loader', $e);
            $this->assertContains('Unable to find template', $e->getMessage());
        }
    }

    public function testSimpleTemplate()
    {
        $templateFileName = 'template.twig';

        file_put_contents($this->templateDir.'/'.$templateFileName,
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

        file_put_contents($this->templateDir.'/'.$templateFileName,
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
        $targetFileName   = 'rendered.txt';
        $expectedFileName = 'expected.txt';

        file_put_contents($this->templateDir.'/'.$templateFileName,
            'Template for "{{ book.title }}" (by {{ author }})'
        );

        file_put_contents($this->templateDir.'/'.$expectedFileName,
            'Template for "Custom Test Book Title" (by easybook tests)'
        );

        $this->app->render($templateFileName, array('author' => 'easybook tests'),
            $this->templateDir.'/'.$targetFileName
        );

        $this->assertFileEquals(
            $this->templateDir.'/'.$expectedFileName,
            $this->templateDir.'/'.$targetFileName
        );
    }
}