<?php declare(strict_types=1);

namespace Easybook\Tests\Plugins;

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
     *
     * @param string $inputFilePath        The contents to be parsed
     * @param string $expectedFilePath     The expected result of parsing the contents
     * @param bool   $enableCodeHightlight Whether or not code listings should be highlighted
     */
    public function testCodeBlocksTypes(
        string $inputFilePath,
        string $expectedFilePath,
        bool $enableCodeHightlight
    ): void {
        $item = [
            'config' => ['format' => 'md'],
            'original' => file_get_contents(__DIR__ . '/fixtures/code/' . $inputFilePath),
            'content' => '',
        ];

        // execute pre-parse method of the plugin
        $parseEvent = new ItemAwareEvent($item);
        $this->codePluginEventSubscriber->parseCodeBlocks(new ItemAwareEvent($item));

        // parse the item original content
        $parseEvent->changeItemProperty(
            'contant',
            $this->markdownParser->transform($parseEvent->getItemProperty('content'))
        );

        // execute post-parse method of the plugin
        $this->codePluginEventSubscriber->fixYamlStyleComments($parseEvent);
        $item = $parseEvent->getItem();

        $this->assertSame(file_get_contents(__DIR__ . '/fixtures/code/' . $expectedFilePath), $item['content']);
    }

    public function getCodeBlockConfiguration(): Iterator
    {
        yield ['input_1.md', 'expected_easybook_type_enabled_highlight.html'];
        yield ['input_2.md', 'expected_fenced_type_enabled_highlight.html'];
        yield ['input_3.md', 'expected_github_type_enabled_highlight.html'];
    }
}
