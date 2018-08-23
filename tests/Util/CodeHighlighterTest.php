<?php declare(strict_types=1);

namespace Easybook\Tests\Util;

use Easybook\Tests\AbstractContainerAwareTestCase;
use Easybook\Util\CodeHighlighter;
use Iterator;

final class CodeHighlighterTest extends AbstractContainerAwareTestCase
{
    /**
     * @var CodeHighlighter
     */
    private $codeHighlighter;

    protected function setUp(): void
    {
        $this->codeHighlighter = $this->container->get(CodeHighlighter::class);
    }

    /**
     * @dataProvider provideHighlightFixtures()
     */
    public function testHighlight(string $language, string $originalFilePath, string $highlightedFilePath): void
    {
        $originalFileContent = file_get_contents($originalFilePath);

        $this->assertStringEqualsFile(
            $highlightedFilePath,
            $this->codeHighlighter->highlight($originalFileContent, $language)
        );
    }

    public function provideHighlightFixtures(): Iterator
    {
        yield ['html', __DIR__ . '/fixtures/highlight/input/html.txt', __DIR__ . '/fixtures/highlight/output/html.txt'];
        yield ['php', __DIR__ . '/fixtures/highlight/input/php.txt', __DIR__ . '/fixtures/highlight/output/php.txt'];
    }
}
