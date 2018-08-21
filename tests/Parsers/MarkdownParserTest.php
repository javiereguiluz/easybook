<?php declare(strict_types=1);

namespace Easybook\Tests\Parsers;

use Easybook\Parsers\MarkdownParser;
use Easybook\Tests\AbstractContainerAwareTestCase;

final class MarkdownParserTest extends AbstractContainerAwareTestCase
{
    protected $app;

    protected $parser;

    protected $fixtures_dir;

    protected function setUp(): void
    {
        $this->fixtures_dir = __DIR__ . '/fixtures';
    }

    public function testOriginalMarkdown(): void
    {
        $this->app['parser.options'] = ['markdown_syntax' => 'original'];
        $this->parser = new MarkdownParser($this->app);

        // get...
        $docs = $this->app['finder']
            ->files()
            ->name('*.md')
            ->depth(0)
            ->in($this->fixtures_dir . '/input/markdown-original');

        $this->parseAndTestDocs($docs, '[Markdown] Original Syntax:');
    }

    public function testPHPMarkdown(): void
    {
        $this->app['parser.options'] = ['markdown_syntax' => 'php-markdown-extra'];
        $this->parser = new MarkdownParser($this->app);

        // get...
        $docs = $this->app['finder']
            ->files()
            ->name('*.md')
            ->notName('Backslash escapes.md')
            ->depth(0)
            ->in($this->fixtures_dir . '/input/markdown-php');

        $this->parseAndTestDocs($docs, '[Markdown] PHP Syntax:');
    }

    public function testPHPExtraMarkdown(): void
    {
        $this->app['parser.options'] = ['markdown_syntax' => 'php-markdown-extra'];
        $this->parser = new MarkdownParser($this->app);

        // get...
        $docs = $this->app['finder']
            ->files()
            ->name('*.md')
            ->depth(0)
            ->in($this->fixtures_dir . '/input/markdown-php-extra');

        $this->parseAndTestDocs($docs, '[Markdown] PHP Extra Syntax:');
    }

    public function testEasybookMarkdown(): void
    {
        $this->app['parser.options'] = [
            'markdown_syntax' => 'easybook',
            'code_block_type' => 'easybook',
        ];
        $this->parser = new MarkdownParser($this->app);

        $docs = $this->app['finder']
            ->files()
            ->name('*.md')
            ->depth(0)
            ->in($this->fixtures_dir . '/input/markdown-easybook');

        $this->parseAndTestDocs($docs, '[Markdown] easybook Syntax:');
    }

    private function parseAndTestDocs($docs, $message = ''): void
    {
        foreach ($docs as $doc) {
            $inputFilepath = $doc->getPathName();
            $parsed = $this->parser->transform(file_get_contents($inputFilepath));

            $expectedFilepath = str_replace(
                ['/fixtures/input/', '.md'],
                ['/fixtures/expected/', '.html'],
                $inputFilepath
            );
            $expected = file_get_contents($expectedFilepath);

            $this->assertSame($expected, $parsed, $message . ' ' . $doc->getRelativePathname());

            $this->app['slugger.generated_slugs'] = [];
        }
    }
}
