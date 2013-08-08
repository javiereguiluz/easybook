<?php

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Easybook\Tests\Commands;

use Easybook\DependencyInjection\Application;
use Easybook\Parsers\MarkdownParser;

class MarkdownParserTest extends \PHPUnit_Framework_TestCase
{
    protected $app;
    protected $parser;
    protected $fixtures_dir;

    public function setUp()
    {
        $this->app = new Application();
        $this->fixtures_dir = __DIR__.'/fixtures';
    }

    public function testOriginalMarkdown()
    {
        $this->app['parser.options'] = array('markdown_syntax' => 'original');
        $this->parser = new MarkdownParser($this->app);

        $docs = $this->app['finder']
            ->files()
            ->name('*.md')
            ->depth(0)
            ->in($this->fixtures_dir.'/input/markdown-original')
        ;

        $this->parseAndTestDocs($docs, '[Markdown] Original Syntax:');
    }

    public function testPHPMarkdown()
    {
        $this->app['parser.options'] = array('markdown_syntax' => 'php-markdown-extra');
        $this->parser = new MarkdownParser($this->app);

        $docs = $this->app['finder']
            ->files()
            ->name('*.md')
            ->notName('Backslash escapes.md')
            ->depth(0)
            ->in($this->fixtures_dir.'/input/markdown-php')
        ;

        $this->parseAndTestDocs($docs, '[Markdown] PHP Syntax:');
    }

    public function testPHPExtraMarkdown()
    {
        $this->app['parser.options'] = array('markdown_syntax' => 'php-markdown-extra');
        $this->parser = new MarkdownParser($this->app);

        $docs = $this->app['finder']
            ->files()
            ->name('*.md')
            ->depth(0)
            ->in($this->fixtures_dir.'/input/markdown-php-extra')
        ;

        $this->parseAndTestDocs($docs, '[Markdown] PHP Extra Syntax:');
    }

    public function testEasybookMarkdown()
    {
        $this->app['parser.options'] = array(
            'markdown_syntax' => 'easybook',
            'code_block_type' => 'easybook',
        );
        $this->parser = new MarkdownParser($this->app);

        $docs = $this->app['finder']
            ->files()
            ->name('*.md')
            ->depth(0)
            ->in($this->fixtures_dir.'/input/markdown-easybook')
        ;

        $this->parseAndTestDocs($docs, '[Markdown] easybook Syntax:');
    }

    private function parseAndTestDocs($docs, $message = '')
    {
        foreach ($docs as $doc) {
            $inputFilepath = $doc->getPathName();
            $parsed = $this->parser->transform(file_get_contents($inputFilepath));

            $expectedFilepath = str_replace(
                array('/fixtures/input/', '.md'),
                array('/fixtures/expected/', '.html'),
                $inputFilepath
            );
            $expected = file_get_contents($expectedFilepath);

            $this->assertEquals($expected, $parsed, $message.' '.$doc->getRelativePathname());
        }
    }
}
