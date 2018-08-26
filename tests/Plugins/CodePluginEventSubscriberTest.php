<?php declare(strict_types=1);

namespace Easybook\Tests\Plugins;

use Easybook\Book\Item;
use Easybook\Book\Provider\EditionProvider;
use Easybook\Events\ItemAwareEvent;
use Easybook\Plugins\CodePluginEventSubscriber;
use Easybook\Tests\AbstractContainerAwareTestCase;
use Iterator;
use Michelf\MarkdownExtra;

final class CodePluginEventSubscriberTest extends AbstractContainerAwareTestCase
{
    /**
     * @var CodePluginEventSubscriber
     */
    private $codePluginEventSubscriber;

    /**
     * @var EditionProvider
     */
    private $editionProvider;

    /**
     * @var MarkdownExtra
     */
    private $markdownExtra;

    protected function setUp(): void
    {
        $this->markdownExtra = $this->container->get(MarkdownExtra::class);
        $this->codePluginEventSubscriber = $this->container->get(CodePluginEventSubscriber::class);
        $this->editionProvider = $this->container->get(EditionProvider::class);
    }

    /**
     * @dataProvider getCodeBlockConfiguration()
     */
    public function testCodeBlocksTypes(string $inputFilePath, string $expectedFilePath): void
    {
        $this->editionProvider->setEdition('pdf');

        $content = file_get_contents(__DIR__ . '/fixtures/code/' . $inputFilePath);

        /** @var Item $item */
        $item = Item::createFromOriginal($content);

        // execute pre-parse method of the plugin
        $itemAwareEvent = new ItemAwareEvent($item);
        $this->codePluginEventSubscriber->parseCodeBlocks($itemAwareEvent);

        // parse the item original content
        $item->changeContent($this->markdownExtra->transform($item->getContent()));

        $this->assertStringEqualsFile(__DIR__ . '/fixtures/code/' . $expectedFilePath, $item->getContent());
    }

    public function getCodeBlockConfiguration(): Iterator
    {
        yield ['input_1.md', 'expected_easybook_type_enabled_highlight.html'];
        yield ['input_2.md', 'expected_fenced_type_enabled_highlight.html'];
        yield ['input_3.md', 'expected_github_type_enabled_highlight.html'];
    }
}
