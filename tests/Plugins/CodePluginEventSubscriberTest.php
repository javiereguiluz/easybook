<?php declare(strict_types=1);

namespace Easybook\Tests\Plugins;

use Easybook\Book\Item;
use Easybook\Events\ItemAwareEvent;
use Easybook\Parsers\MarkdownParser;
use Easybook\Plugins\CodePluginEventSubscriber;
use Easybook\Tests\AbstractContainerAwareTestCase;
use Iterator;

final class CodePluginEventSubscriberTest extends AbstractContainerAwareTestCase
{
    /**
     * @var MarkdownParser
     */
    private $markdownParser;

    /**
     * @var CodePluginEventSubscriber
     */
    private $codePluginEventSubscriber;

    protected function setUp(): void
    {
        $this->markdownParser = $this->container->get(MarkdownParser::class);
        $this->codePluginEventSubscriber = $this->container->get(CodePluginEventSubscriber::class);
    }

    /**
     * @dataProvider getCodeBlockConfiguration()
     */
    public function testCodeBlocksTypes(string $inputFilePath, string $expectedFilePath): void
    {
        // new item...
        /** @var Item $item */
        $item = [
            'original' => file_get_contents(__DIR__ . '/fixtures/code/' . $inputFilePath),
            'content' => '',
        ];

        // execute pre-parse method of the plugin
        $itemAwareEvent = new ItemAwareEvent($item);
        $this->codePluginEventSubscriber->parseCodeBlocks($itemAwareEvent);

        // parse the item original content
        $item->changeContent($this->markdownParser->transform($item->getContent()));

        $this->assertSame(file_get_contents(__DIR__ . '/fixtures/code/' . $expectedFilePath), $item->getContent());
    }

    public function getCodeBlockConfiguration(): Iterator
    {
        yield ['input_1.md', 'expected_easybook_type_enabled_highlight.html'];
        yield ['input_2.md', 'expected_fenced_type_enabled_highlight.html'];
        yield ['input_3.md', 'expected_github_type_enabled_highlight.html'];
    }
}
