<?php declare(strict_types=1);

namespace Easybook\Tests\Parsers;

use Easybook\Parsers\MarkdownParser;
use Easybook\Tests\AbstractContainerAwareTestCase;
use Easybook\Util\Slugger;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

final class MarkdownParserTest extends AbstractContainerAwareTestCase
{
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
        $this->markdownParser = $this->container->get(MarkdownParser::class);
        $this->finder = $this->container->get(Finder::class);
        $this->slugger = $this->container->get(Slugger::class);
    }

    public function testPHPMarkdown(): void
    {
        $docs = $this->finder->files()
            ->name('*.md')
            ->notName('Backslash escapes.md')
            ->depth(0)
            ->in(__DIR__ . '/fixtures/input/markdown-php')
            ->getIterator();

        $this->parseAndTestDocs($docs, '[Markdown] PHP Syntax:');
    }

    public function testPHPExtraMarkdown(): void
    {
        $docs = $this->finder->files()
            ->name('*.md')
            ->depth(0)
            ->in(__DIR__ . '/fixtures/input/markdown-php-extra')
            ->getIterator();

        $this->parseAndTestDocs($docs, '[Markdown] PHP Extra Syntax:');
    }

    /**
     * @param SplFileInfo[] $docs
     */
    private function parseAndTestDocs(iterable $docs, string $message): void
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
