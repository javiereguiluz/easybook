<?php declare(strict_types=1);

namespace Easybook\Tests\Plugins;

use Easybook\Events\ItemAwareEvent;
use Easybook\Parsers\MarkdownParser;
use Easybook\Plugins\CodePluginEventSubscriber;
use Easybook\Tests\AbstractContainerAwareTestCase;

final class CodePluginTest extends AbstractContainerAwareTestCase
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
        $app = $this->getApp($enableCodeHightlight);

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
        $this->codePluginEventSubscriber->fixParsedCodeBlocks($parseEvent);
        $item = $parseEvent->getItem();

        $this->assertSame(file_get_contents(__DIR__ . '/fixtures/code/' . $expectedFilePath), $item['content']);
    }

    /**
     * @return mixed[]
     */
    public function getCodeBlockConfiguration(): array
    {
        return [
            ['input_1.md', 'expected_easybook_type_disabled_highlight.html', false],
            ['input_1.md', 'expected_easybook_type_enabled_highlight.html', true],

            ['input_2.md', 'expected_fenced_type_disabled_highlight.html', false],
            ['input_2.md', 'expected_fenced_type_enabled_highlight.html', true],

            ['input_3.md', 'expected_github_type_disabled_highlight.html', false],
            ['input_3.md', 'expected_github_type_enabled_highlight.html', true],
        ];
    }

    private function getApp(bool $enableCodeHightlight)
    {
        // @todo use parameters

        $app['book_slug'] = 'test_book';
        $app['publishing.edition'] = 'test_edition';
        $app['publishing.book.config'] = [
            'book' => [
                'slug' => 'test_book',
                'language' => 'en',
                'editions' => [
                    'test_edition' => [
                        'format' => 'html',
                        'highlight_code' => $enableCodeHightlight,
                        'theme' => 'clean',
                    ],
                ],
            ],
        ];

        return $app;
    }
}
