<?php declare(strict_types=1);

namespace Easybook\Tests\Parsers;

use Easybook\Parsers\MarkdownParser;
use Easybook\Tests\AbstractContainerAwareTestCase;
use Easybook\Util\Slugger;
use Symfony\Component\Finder\Finder;

final class MarkdownParserTest extends AbstractContainerAwareTestCase
{
    /**
     * @var string
     */
    private $fixturesDir;

    /**
     * @var MarkdownParser
     */
    private $markdownParser;

    /**
     * @var Finder
     */
    private $finder;

    /**
     * @var Slugger
     */
    private $slugger;

    protected function setUp(): void
    {
        $this->fixturesDir = __DIR__ . '/fixtures';
        $this->markdownParser = $this->container->get(MarkdownParser::class);
        $this->finder = $this->container->get(Finder::class);
        $this->slugger = $this->container->get(Slugger::class);
    }

    public function testPHPMarkdown(): void
    {
        // get...
        $docs = $this->finder->files()
            ->name('*.md')
            ->notName('Backslash escapes.md')
            ->depth(0)
            ->in($this->fixturesDir . '/input/markdown-php');

        $this->parseAndTestDocs($docs, '[Markdown] PHP Syntax:');
    }

    public function testPHPExtraMarkdown(): void
    {
        // get...
        $docs = $this->finder->files()
            ->name('*.md')
            ->depth(0)
            ->in($this->fixturesDir . '/input/markdown-php-extra');

        $this->parseAndTestDocs($docs, '[Markdown] PHP Extra Syntax:');
    }

    /**
     * @param \SplFileInfo[] $docs
     */
    private function parseAndTestDocs(array $docs, string $message = ''): void
    {
        foreach ($docs as $doc) {
            $inputFilepath = $doc->getPathName();
            $parsed = $this->markdownParser->transform(file_get_contents($inputFilepath));

            $expectedFilepath = str_replace(
                ['/fixtures/input/', '.md'],
                ['/fixtures/expected/', '.html'],
                $inputFilepath
            );
            $expected = file_get_contents($expectedFilepath);

            $this->assertSame($expected, $parsed, $message . ' ' . $doc->getRelativePathname());

            $this->slugger->resetGeneratedSlugs();
        }
    }
}
